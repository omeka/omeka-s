<?php
namespace Omeka\Controller\SiteAdmin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\SiteForm;
use Omeka\Form\SitePageForm;
use Omeka\Form\SiteResourcesForm;
use Omeka\Form\SiteSettingsForm;
use Omeka\Mvc\Exception;
use Omeka\Site\Navigation\Link\Manager as LinkManager;
use Omeka\Site\Navigation\Translator;
use Omeka\Site\Theme\Manager as ThemeManager;
use Laminas\Form\Form;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var ThemeManager
     */
    protected $themes;

    /**
     * @var LinkManager
     */
    protected $navLinks;

    /**
     * @var Translator
     */
    protected $navTranslator;

    public function __construct(ThemeManager $themes, LinkManager $navLinks,
        Translator $navTranslator
    ) {
        $this->themes = $themes;
        $this->navLinks = $navLinks;
        $this->navTranslator = $navTranslator;
    }

    public function indexAction()
    {
        $this->setBrowseDefaults('title', 'asc');
        $response = $this->api()->search('sites', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

        $view = new ViewModel;
        $view->setVariable('sites', $response->getContent());
        return $view;
    }

    public function addAction()
    {
        $form = $this->getForm(SiteForm::class);
        $themes = $this->themes->getThemes();
        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                $formData = $form->getData();
                // Set o:assign_new_items to true by default. This is the legacy
                // setting from before v3.0 when sites were using the item pool.
                $formData['o:assign_new_items'] = true;
                $formData['o:theme'] = $postData['o:theme'];
                $response = $this->api($form)->create('sites', $formData);
                if ($response) {
                    $this->messenger()->addSuccess('Site successfully created'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('themes', $themes);
        return $view;
    }

    public function editAction()
    {
        $site = $this->currentSite();
        $form = $this->getForm(SiteForm::class);
        $form->setAttribute('action', $this->url()->fromRoute(null, [], true));
        $settingsForm = $this->getForm(SiteSettingsForm::class);
        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            $settingsForm->setData($postData);
            if ($form->isValid() && $settingsForm->isValid()) {
                // Prepare site form data.
                $formData = $form->getData();
                unset($formData['csrf']);
                $formData['o:assign_new_items'] = $postData['general']['o:assign_new_items'];
                // Prepare settings form data.
                $settingsFormData = $settingsForm->getData();
                unset($settingsFormData['csrf']);
                unset($settingsFormData['general']['o:assign_new_items']);
                // Update settings.
                $settingsFormFieldsets = $settingsForm->getFieldsets();
                foreach ($settingsFormData as $id => $value) {
                    if (array_key_exists($id, $settingsFormFieldsets) && is_array($value)) {
                        // De-nest fieldsets.
                        foreach ($value as $fieldsetId => $fieldsetValue) {
                            $this->siteSettings()->set($fieldsetId, $fieldsetValue);
                        }
                    } else {
                        $this->siteSettings()->set($id, $value);
                    }
                }
                // Update site.
                $response = $this->api($form)->update('sites', $site->id(), $formData, [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Site successfully updated'); // @translate
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addFormErrors($form);
                $this->messenger()->addFormErrors($settingsForm);
            }
        } else {
            // Prepare form data on first load.
            $form->setData($site->jsonSerialize());
            $settingsForm->get('general')->get('o:assign_new_items')->setValue($site->assignNewItems());
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('resourceClassId', $this->params()->fromQuery('resource_class_id'));
        $view->setVariable('itemSetId', $this->params()->fromQuery('item_set_id'));
        $view->setVariable('form', $form);
        $view->setVariable('settingsForm', $settingsForm);
        return $view;
    }

    public function addPageAction()
    {
        $site = $this->currentSite();
        $form = $this->getForm(SitePageForm::class, ['addPage' => $site->userIsAllowed('update')]);

        if ($this->getRequest()->isPost()) {
            $post = $this->params()->fromPost();
            $form->setData($post);
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o:site']['o:id'] = $site->id();
                $formData['o:is_public'] = !empty($post['o:is_public']);
                $response = $this->api($form)->create('site_pages', $formData);
                if ($response) {
                    $page = $response->getContent();
                    if (isset($formData['add_to_navigation']) && $formData['add_to_navigation']) {
                        // Add page to navigation.
                        $navigation = $site->navigation();
                        $navigation[] = [
                            'type' => 'page',
                            'links' => [],
                            'data' => ['id' => $page->id(), 'label' => null],
                        ];
                        $this->api()->update('sites', $site->id(), ['o:navigation' => $navigation], [], ['isPartial' => true]);
                    }
                    $this->messenger()->addSuccess('Page successfully created'); // @translate
                    return $this->redirect()->toRoute(
                        'admin/site/slug/page/default',
                        [
                            'page-slug' => $page->slug(),
                            'action' => 'edit',
                        ],
                        true
                    );
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('form', $form);
        return $view;
    }

    public function navigationAction()
    {
        $site = $this->currentSite();
        $form = $this->getForm(Form::class)->setAttribute('id', 'site-form');
        $form->add([
            'name' => 'o:homepage[o:id]',
            'type' => 'Omeka\Form\Element\SitePageSelect',
            'options' => [
                'label' => 'Homepage', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'value' => $site->homepage() ? $site->homepage()->id() : null,
                'class' => 'chosen-select',
                'data-placeholder' => 'First page in navigation', // @translate
            ],
        ]);
        $form->getInputFilter()->add([
            'name' => 'o:homepage[o:id]',
            'allow_empty' => true,
        ]);

        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $jstree = json_decode($formData['jstree'], true);
            $formData['o:navigation'] = $this->navTranslator->fromJstree($jstree);
            $form->setData($formData);
            if ($form->isValid()) {
                $response = $this->api($form)->update('sites', $site->id(), $formData, [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Navigation successfully updated'); // @translate
                    return $this->redirect()->refresh();
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('navTree', $this->navTranslator->toJstree($site));
        $view->setVariable('form', $form);
        $view->setVariable('site', $site);
        return $view;
    }

    public function resourcesAction()
    {
        $site = $this->currentSite();
        $form = $this->getForm(SiteResourcesForm::class)->setAttribute('id', 'site-form');

        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $form->setData($formData);
            if ($form->isValid()) {
                $updateData = [
                    'o:site_item_set' => $formData['o:site_item_set'] ?? [],
                ];
                $itemPool = $formData;
                unset(
                    $itemPool['siteresourcesform_csrf'],
                    $itemPool['item_assignment_action'],
                    $itemPool['save_search'],
                    $itemPool['o:site_item_set']
                );
                $updateData['o:item_pool'] = $formData['save_search'] ? $itemPool : $site->itemPool();
                if ($formData['item_assignment_action']) {
                    $this->jobDispatcher()->dispatch('Omeka\Job\UpdateSiteItems', [
                        'sites' => [$site->id() => $itemPool],
                        'action' => $formData['item_assignment_action'],
                    ]);
                    $this->messenger()->addSuccess('Item assignment in progress. To see the new item count, refresh the page.'); // @translate
                }
                $response = $this->api($form)->update('sites', $site->id(), $updateData, [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Site resources successfully updated'); // @translate
                    return $this->redirect()->refresh();
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $itemCount = $this->api()
            ->search('items', ['limit' => 0, 'site_id' => $site->id()])
            ->getTotalResults();
        $itemSets = [];
        foreach ($site->siteItemSets() as $siteItemSet) {
            $itemSet = $siteItemSet->itemSet();
            $owner = $itemSet->owner();
            $itemSets[] = [
                'id' => $itemSet->id(),
                'title' => $itemSet->displayTitle(),
                'email' => $owner ? $owner->email() : null,
            ];
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('form', $form);
        $view->setVariable('itemCount', $itemCount);
        $view->setVariable('itemSets', $itemSets);
        return $view;
    }

    public function usersAction()
    {
        $site = $this->currentSite();
        $form = $this->getForm(Form::class)->setAttribute('id', 'site-form');

        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $form->setData($formData);
            if ($form->isValid()) {
                $response = $this->api($form)->update('sites', $site->id(), $formData, [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('User permissions successfully updated'); // @translate
                    return $this->redirect()->refresh();
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $users = $this->api()->search('users', ['sort_by' => 'name']);

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('form', $form);
        $view->setVariable('users', $users->getContent());
        return $view;
    }

    public function themeAction()
    {
        $site = $this->currentSite();
        if (!$site->userIsAllowed('update')) {
            throw new Exception\PermissionDeniedException(
                'User does not have permission to edit site theme settings'
            );
        }
        $form = $this->getForm(Form::class)->setAttribute('id', 'site-theme-form');
        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $form->setData($formData);
            if ($form->isValid()) {
                $response = $this->api($form)->update('sites', $site->id(), $formData, [], ['isPartial' => true]);
                if ($response) {
                    $this->messenger()->addSuccess('Site theme successfully updated'); // @translate
                    return $this->redirect()->refresh();
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('site', $site);
        $view->setVariable('themes', $this->themes->getThemes());
        $view->setVariable('currentTheme', $this->themes->getCurrentTheme());
        return $view;
    }

    public function themeSettingsAction()
    {
        $site = $this->currentSite();

        if (!$site->userIsAllowed('update')) {
            throw new Exception\PermissionDeniedException(
                'User does not have permission to edit site theme settings'
            );
        }

        $theme = $this->themes->getTheme($site->theme());
        if (!$theme->isConfigurable()) {
            throw new Exception\RuntimeException(
                'The current theme is not configurable.'
            );
        }

        $config = $theme->getConfigSpec();
        $view = new ViewModel;

        /** @var Form $form */
        $form = $this->getForm(Form::class)->setAttribute('id', 'site-form');

        foreach ($config['elements'] as $elementSpec) {
            $form->add($elementSpec);
        }

        // Fix to manage empty values for selects and multicheckboxes.
        $inputFilter = $form->getInputFilter();
        foreach ($form->getElements() as $element) {
            if ($element instanceof \Laminas\Form\Element\MultiCheckbox
                || ($element instanceof \Laminas\Form\Element\Select
                    && $element->getOption('empty_option') !== null)
            ) {
                $inputFilter->add([
                    'name' => $element->getName(),
                    'allow_empty' => true,
                ]);
            }
        }

        $oldSettings = $this->siteSettings()->get($theme->getSettingsKey());
        if ($oldSettings) {
            $form->setData($oldSettings);
        }

        $view->setVariable('form', $form);
        $view->setVariable('theme', $theme);
        if (!$this->getRequest()->isPost()) {
            return $view;
        }

        $postData = $this->params()->fromPost();
        $form->setData($postData);
        if ($form->isValid()) {
            $data = $form->getData();
            unset($data['form_csrf']);
            $this->siteSettings()->set($theme->getSettingsKey(), $data);
            $this->messenger()->addSuccess('Theme settings successfully updated'); // @translate
            return $this->redirect()->refresh();
        }

        $this->messenger()->addFormErrors($form);

        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('sites', ['slug' => $this->params('site-slug')]);
                if ($response) {
                    $this->messenger()->addSuccess('Site successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute('admin/site');
    }

    public function showAction()
    {
        $site = $this->currentSite();
        $view = new ViewModel;
        $view->setVariable('site', $site);
        return $view;
    }

    public function deleteConfirmAction()
    {
        $site = $this->currentSite();
        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resourceLabel', 'site'); // @translate
        $view->setVariable('partialPath', 'omeka/site-admin/index/show-details');
        $view->setVariable('resource', $site);
        return $view;
    }

    public function navigationLinkFormAction()
    {
        $site = $this->currentSite();
        $link = $this->navLinks->get($this->params()->fromPost('type'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate($link->getFormTemplate());
        $view->setVariable('data', $this->params()->fromPost('data'));
        $view->setVariable('site', $site);
        $view->setVariable('link', $link);
        return $view;
    }

    public function sidebarItemSelectAction()
    {
        $this->setBrowseDefaults('created');
        $site = $this->currentSite();

        $query = $this->params()->fromQuery();
        $query['site_id'] = $site->id();

        $response = $this->api()->search('items', $query);
        $items = $response->getContent();
        $this->paginator($response->getTotalResults());

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('omeka/admin/item/sidebar-select');
        $view->setVariable('search', $this->params()->fromQuery('search'));
        $view->setVariable('resourceClassId', $this->params()->fromQuery('resource_class_id'));
        $view->setVariable('itemSetId', $this->params()->fromQuery('item_set_id'));
        $view->setVariable('id', $this->params()->fromQuery('id'));
        $view->setVariable('items', $items);
        $view->setVariable('showDetails', false);
        return $view;
    }
}

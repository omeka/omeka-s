<?php
namespace Omeka\Controller\SiteAdmin;

use Omeka\Event\Event;
use Omeka\Form\Form;
use Omeka\Form\ConfirmForm;
use Omeka\Form\SiteForm;
use Omeka\Form\SitePageForm;
use Omeka\Form\SiteSettingsForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $this->setBrowseDefaults('title');
        $response = $this->api()->search('sites', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('sites', $response->getContent());
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, [
                'button_value' => $this->translate('Confirm Delete'),
            ]
        ));
        return $view;
    }

    public function addAction()
    {
        $form = new SiteForm($this->getServiceLocator());
        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $formData['o:item_pool'] = json_decode($formData['item_pool'], true);
            $form->setData($formData);
            if ($form->isValid()) {
                $response = $this->api()->create('sites', $formData);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Site created.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function editAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $form = new SiteForm($serviceLocator);
        $readResponse = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);

        $site = $readResponse->getContent();
        $id = $site->id();
        $data = $site->jsonSerialize();
        $form->setData($data);
        $this->layout()->setVariable('site', $site);

        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $form->setData($formData);
            if ($form->isValid()) {
                $response = $this->api()->update('sites', $id, $formData, [], true);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Site updated.');
                    // Explicitly re-read the site URL instead of using
                    // refresh() so we catch updates to the slug
                    return $this->redirect()->toUrl($site->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('form', $form);
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, [
                'button_value' => $this->translate('Confirm Delete'),
            ]
        ));
        return $view;
    }

    public function settingsAction()
    {
        $site = $this->api()->read('sites', [
            'slug' => $this->params('site-slug'),
        ])->getContent();

        $form = new SiteSettingsForm($this->getServiceLocator());
        $event = new Event(Event::SITE_SETTINGS_FORM, $this, [
            'form' => $form,
        ]);
        $this->getEventManager()->trigger($event);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $settings = $this->getServiceLocator()->get('Omeka\SiteSettings');
                $data = $form->getData();
                unset($data['csrf']);
                foreach ($data as $id => $value) {
                    $settings->set($id, $value);
                }
                $this->messenger()->addSuccess('Settings updated.');
                return $this->redirect()->refresh();
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('form', $form);
        $this->layout()->setVariable('site', $site);
        return $view;
    }

    public function addPageAction()
    {
        $form = new SitePageForm($this->getServiceLocator());

        $readResponse = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);
        $site = $readResponse->getContent();
        $this->layout()->setVariable('site', $site);
        $id = $site->id();

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o:site']['o:id'] = $id;
                $response = $this->api()->create('site_pages', $formData);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Page created.');
                    return $this->redirect()->toRoute(
                        'admin/site/page',
                        ['action' => 'index'],
                        true
                    );
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('form', $form);
        return $view;
    }

    public function navigationAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $translator = $serviceLocator->get('Omeka\Site\NavigationTranslator');
        $readResponse = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);

        $site = $readResponse->getContent();
        $id = $site->id();
        $this->layout()->setVariable('site', $site);
        $form = new Form($this->getServiceLocator());

        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $jstree = json_decode($formData['jstree'], true);
            $formData['o:navigation'] = $translator->fromJstree($jstree);
            $form->setData($formData);
            if ($form->isValid()) {
                $response = $this->api()->update('sites', $id, $formData, [], true);
                if (!$response->isError()) {
                    $this->messenger()->addSuccess('Navigation updated.');
                    return $this->redirect()->refresh();
                }
                $this->messenger()->addErrors($response->getErrors());
            }
        }

        $view = new ViewModel;
        $view->setVariable('navTree', $translator->toJstree($site));
        $view->setVariable('form', $form);
        $view->setVariable('site', $site);
        return $view;
    }

    public function itemPoolAction()
    {
        $readResponse = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);

        $site = $readResponse->getContent();
        $id = $site->id();
        $this->layout()->setVariable('site', $site);

        $form = new Form($this->getServiceLocator());

        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $formData['o:item_pool'] = json_decode($formData['item_pool'], true);
            $form->setData($formData);
            if ($form->isValid()) {
                $response = $this->api()->update('sites', $id, $formData, [], true);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Item pool updated.');
                    return $this->redirect()->refresh();
                }
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('form', $form);
        return $view;
    }

    public function usersAction()
    {
        $readResponse = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);

        $site = $readResponse->getContent();
        $id = $site->id();
        $this->layout()->setVariable('site', $site);
        $data = $site->jsonSerialize();

        $form = new Form($this->getServiceLocator());

        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $form->setData($formData);
            if ($form->isValid()) {
                $response = $this->api()->update('sites', $id, $formData, [], true);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('User permissions updated.');
                    return $this->redirect()->refresh();
                }
                $this->messenger()->addError('There was an error during validation');
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
        $readResponse = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);
        $site = $readResponse->getContent();

        $services = $this->getServiceLocator();
        $tm = $services->get('Omeka\Site\ThemeManager');
        $settings = $services->get('Omeka\SiteSettings');

        $theme = $tm->getTheme($site->theme());
        $settingsKey = $theme->getSettingsKey();
        $config = $theme->getConfigSpec();

        $view = new ViewModel;
        $this->layout()->setVariable('site', $site);
        if (!($config && $config['elements'])) {
            return $view;
        }

        $form = new Form($services);
        foreach ($config['elements'] as $elementSpec) {
            $form->add($elementSpec);
        }

        $oldSettings = $settings->get($theme->getSettingsKey());
        if ($oldSettings) {
            $form->setData($oldSettings);
        }

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $data = $form->getData();
                unset($data['csrf']);
                $settings->set($settingsKey, $data);
                $this->messenger()->addSuccess('Theme settings updated.');
                return $this->redirect()->refresh();
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('sites', [
                    'slug' => $this->params('site-slug')]
                );
                if ($response->isError()) {
                    $this->messenger()->addError('Site could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Site successfully deleted');
                }
            } else {
                $this->messenger()->addError('Site could not be deleted');
            }
        }
        return $this->redirect()->toRoute('admin/site');
    }

    public function showAction()
    {
        $response = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);

        $view = new ViewModel;
        $site = $response->getContent();
        $this->layout()->setVariable('site', $site);
        $view->setVariable('site', $site);
        return $view;
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);
        $site = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resourceLabel', 'site');
        $view->setVariable('partialPath', 'omeka/site-admin/index/show-details');
        $view->setVariable('resource', $site);
        return $view;
    }

    public function navigationLinkFormAction()
    {
        $site = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ])->getContent();
        $link = $this->getServiceLocator()
            ->get('Omeka\Site\NavigationLinkManager')
            ->get($this->params()->fromPost('type'));

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
        $site = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ])->getContent();
        $itemPool = is_array($site->itemPool()) ? $site->itemPool()  : [];
        $query = array_merge($itemPool, $this->params()->fromQuery());

        $response = $this->api()->search('items', $query);
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('items', $response->getContent());
        $value = $this->params()->fromQuery('value');
        $view->setVariable('searchValue', $value ? $value['in'][0] : '');
        $view->setVariable('showDetails', false);
        $view->setTerminal(true);
        $view->setTemplate('omeka/admin/item/sidebar-select');
        return $view;
    }
}

<?php
namespace Omeka\Controller\SiteAdmin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\SiteForm;
use Omeka\Form\SitePageForm;
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
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Delete'),
            )
        ));
        return $view;
    }

    public function addAction()
    {
        $form = new SiteForm($this->getServiceLocator());
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
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
        $form = new SiteForm($this->getServiceLocator());
        $readResponse = $this->api()->read('sites', array(
            'slug' => $this->params('site-slug')
        ));

        $site = $readResponse->getContent();
        $id = $site->id();
        $data = $site->jsonSerialize();
        $form->setData($data);

        if ($this->getRequest()->isPost()) {
            $formData = $this->params()->fromPost();
            $formData['o:navigation'] = $this->fromJstree($formData['jstree']);
            $form->setData($formData);
            if ($form->isValid()) {
                $response = $this->api()->update('sites', $id, $formData);
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

        $users = $this->api()->search('users', array('sort_by' => 'name'));

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('jstree', $this->toJstree($site));
        $view->setVariable('users', $users->getContent());
        $view->setVariable('form', $form);
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Delete'),
            )
        ));
        return $view;
    }

    /**
     * Convert jsTree data to site o:navigation data.
     *
     * @param string $json
     * @return array
     */
    public function fromJstree($json)
    {
        $buildNavigation = function ($pagesIn) use (&$buildNavigation) {
            $pagesOut = array();
            foreach ($pagesIn as $key => $page) {
                $pagesOut[$key] = $page['data'];
                if ($page['children']) {
                    $pagesOut[$key]['pages'] = $buildNavigation($page['children']);
                }
            }
            return $pagesOut;
        };
        $pages = $buildNavigation(json_decode($json, true));
        return $pages;
    }

    /**
     * Convert site o:navigation data to jsTree data.
     *
     * @param SiteRepresentation $site
     * @return array
     */
    public function toJstree($site)
    {
        $sitePages = $site->pages();
        $siteSlug = $site->slug();

        $buildNavigation = function ($pagesIn)
            use (&$buildNavigation, $siteSlug, $sitePages
        ) {
            $pagesOut = array();
            foreach ($pagesIn as $key => $page) {
                if (!isset($page['type'])) {
                    continue;
                }
                switch ($page['type']) {
                    case 'home':
                        $pagesOut[$key] = array(
                            'text' => 'Home',
                            'data' => array(
                                'type' => 'home',
                            ),
                        );
                        break;
                    case 'browse':
                        $pagesOut[$key] = array(
                            'text' => 'Browse',
                            'data' => array(
                                'type' => 'browse',
                            ),
                        );
                        break;
                    case 'page':
                        if (isset($sitePages[$page['id']])) {
                            $sitePage = $sitePages[$page['id']];
                            $pagesOut[$key] = array(
                                'text' => $sitePage->title(),
                                'data' => array(
                                    'type' => 'page',
                                    'id' => $sitePage->id(),
                                ),
                            );
                        } else {
                            $pagesOut[$key] = array(
                                'text' => '[invalid page]',
                            );
                        }
                        break;
                    default:
                        continue 2;
                }
                if (isset($page['pages'])) {
                    $pagesOut[$key]['children'] = $buildNavigation($page['pages']);
                }
            }
            return $pagesOut;
        };
        $pages = $buildNavigation($site->navigation());
        return json_encode($pages);
    }

    public function addPageAction()
    {
        $form = new SitePageForm($this->getServiceLocator());

        $readResponse = $this->api()->read('sites', array(
            'slug' => $this->params('site-slug')
        ));
        $site = $readResponse->getContent();
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
                    return $this->redirect()->toUrl($site->url());
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

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('sites', array(
                    'slug' => $this->params('site-slug'))
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

    public function showDetailsAction()
    {
        $response = $this->api()->read('sites', array(
            'slug' => $this->params('site-slug')
        ));
        $site = $response->getContent();
        $view = new ViewModel;
        $view->setTerminal(true);

        $view->setVariable('site', $site);
        return $view;
    }
}

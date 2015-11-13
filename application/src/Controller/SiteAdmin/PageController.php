<?php
namespace Omeka\Controller\SiteAdmin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\SitePageForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PageController extends AbstractActionController
{
    public function editAction()
    {
        $form = new SitePageForm($this->getServiceLocator());
        $readResponse = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);
        $site = $readResponse->getContent();
        $siteId = $site->id();
        $this->layout()->setVariable('site', $site);

        $readResponse = $this->api()->read('site_pages', [
            'slug' => $this->params('page-slug'),
            'site' => $siteId
        ]);
        $page = $readResponse->getContent();
        $id = $page->id();

        $data = $page->jsonSerialize();
        $form->setData($data);

        if ($this->getRequest()->isPost()) {
            $post = $this->params()->fromPost();
            $form->setData($post);
            if ($form->isValid()) {
                $response = $this->api()->update('site_pages', $id, $post);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Page updated.');
                    // Explicitly re-read the site URL instead of using
                    // refresh() so we catch updates to the slug
                    return $this->redirect()->toUrl($page->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('page', $page);
        $view->setVariable('form', $form);
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, [
                'button_value' => $this->translate('Confirm Delete'),
            ]
        ));
        return $view;
    }

    public function indexAction()
    {
        $response = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ]);
        $site = $response->getContent();
        $this->layout()->setVariable('site', $site);

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, [
                'button_value' => $this->translate('Confirm Delete'),
            ]
        ));
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete('site_pages', ['slug' => $this->params('page-slug')]);
                if ($response->isError()) {
                    $this->messenger()->addError('Page could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Page successfully deleted');
                }
            } else {
                $this->messenger()->addError('Page could not be deleted');
            }
        }
        return $this->redirect()->toRoute(
            'admin/site/page',
            ['action' => 'index'],
            true
        );
    }

    public function blockAction()
    {
        $layout = $this->params()->fromPost('layout');
        $site = $this->api()->read('sites', [
            'slug' => $this->params('site-slug')
        ])->getContent();
        $helper = $this->getServiceLocator()->get('ViewHelperManager')->get('blockLayout');

        $response = $this->getResponse();
        $response->setContent($helper->form($layout, $site));
        return $response;
    }

    public function attachmentItemOptionsAction()
    {
        $attachedItem = null;
        $attachedMedia = null;

        $itemId = $this->params()->fromPost('itemId');
        if ($itemId) {
            $attachedItem = $this->api()->read('items', $itemId)->getContent();
        }
        $mediaId = $this->params()->fromPost('mediaId');
        if ($mediaId) {
            $attachedMedia = $this->api()->read('media', $mediaId)->getContent();
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('attachedItem', $attachedItem);
        $view->setVariable('attachedMedia', $attachedMedia);
        return $view;
    }
}

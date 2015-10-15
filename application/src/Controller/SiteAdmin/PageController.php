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

    public function blockAction()
    {
        $layout = $this->params()->fromPost('layout');
        $siteId = $this->params()->fromPost('siteId');
        $site = $this->api()->read('sites', $siteId)->getContent();
        $helper = $this->getServiceLocator()->get('ViewHelperManager')->get('blockLayout');

        $response = $this->getResponse();
        $response->setContent($helper->form($layout, $site));
        return $response;
    }
}

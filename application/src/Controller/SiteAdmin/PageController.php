<?php
namespace Omeka\Controller\SiteAdmin;

use Omeka\Form\SitePageForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PageController extends AbstractActionController
{
    public function addAction()
    {
        $form = new SitePageForm($this->getServiceLocator());
        $id = $this->params('site-slug');

        $readResponse = $this->api()->read('sites', $id);
        $site = $readResponse->getContent();

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $formData['o:site']['o:id'] = $site->id();
                $response = $this->api()->create('site_pages', $formData);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Page created.');
                    return $this->redirect()->refresh();
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
}

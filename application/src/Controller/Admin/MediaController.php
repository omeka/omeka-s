<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Form\Form;
use Zend\Form\Element\Csrf;

class MediaController extends AbstractActionController
{
    public function browseAction()
    {
        $response = $this->api()->search('media', array());

        $view = new ViewModel;
        $view->setVariable('medias', $response->getContent());
         $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Delete'),
            )
        ));
        return $view;
    }
    
    public function editAction()
    {
        $form = new ResourceForm($this->getServiceLocator());
        $id = $this->params('id');
        $response = $this->api()->read('media', $id);
        $media = $response->getContent();
        
        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('media', $media);
            if ($this->getRequest()->isPost()) {
                $data = $this->params()->fromPost();
                $form->setData($data);
                if($form->isValid()) {
                    $response = $this->api()->update('media', $id, $data);
                    if ($response->isError()) {
                        $view->setVariable('errors', $response->getErrors());
                        $form->setMessages($response->getErrors());
                    } else {
                        $this->messenger()->addSuccess('Media Updated.');
                        return $this->redirect()->toUrl($response->getContent()->url());
                    }
                } else {
                    $this->messenger()->addError('There was an error during validation');
                }
        }
        return $view;
    }

    public function showAction()
    {
    $response = $this->api()->read('media', $this->params('id'));

    $view = new ViewModel;
    $view->setVariable('media', $response->getContent());
    return $view;
    }
}

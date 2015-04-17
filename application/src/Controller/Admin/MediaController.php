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
    public function indexAction()
    {
        return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
    }
    
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
        $values = array();
        foreach ($media->values() as $term => $property) {
            foreach ($property['values'] as $value) {
                $valuesArray = $value->jsonSerialize(); 
                //look for internal resources and add their titles to the data
                //@TODO: should this be a filter? or maybe a method on the Representation with a param?
                //method would look like valuesArray($terms = array()) and
                //would do the job of looking up bonus values to add to the da
                if ($value->type() == 'resource') {
                    $valueResource = $value->valueResource();
                    $titleValue = $valueResource->value('dcterms:title', array('type' => 'literal'));
                    if ($titleValue) {
                        $valuesArray['dcterms:title'] = $titleValue->value();
                    }
                    $valuesArray['url'] = $valueResource->url();
                }
                $values[$term][] = $valuesArray;
            }
        }
        
        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('media', $media);
        $view->setVariable('values', json_encode($values));
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
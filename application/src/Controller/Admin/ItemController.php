<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Form\Form;
use Zend\Form\Element\Csrf;

class ItemController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'item',
            'action' => 'browse',
        ));
    }

    public function searchAction()
    {
        $view = new ViewModel;
        $view->setVariable('action', $this->url()->fromRoute(
            'admin/default', array('action' => 'browse'), true
        ));
        return $view;
    }

    public function browseAction()
    {
        $view = new ViewModel;

        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('items', $query);

        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('items', $response->getContent());
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Delete'),
            )
        ));
        return $view;
    }

    public function showAction()
    {
        $view = new ViewModel;
        $id = $this->params('id');
        $response = $this->api()->read('items', $id);
        $view->setVariable('item', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);
        $view->setVariable('linkTitle', $linkTitle);
        $response = $this->api()->read(
            'items', array('id' => $this->params('id'))
        );
        $view->setVariable('item', $response->getContent());
        return $view;
    }
    
    public function sidebarSelectAction()
    {
        $view = new ViewModel;
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('items', $query);

        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('items', $response->getContent());
        if (isset($query['value'])) {
            $searchValue = $query['value']['in'][0];
        } else {
            $searchValue = '';
        }
        $view->setVariable('searchValue', $searchValue);
        $view->setTerminal(true);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new ConfirmForm($this->getServiceLocator());
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api()->delete(
                    'items', array('id' => $this->params('id'))
                );
                if ($response->isError()) {
                    $this->messenger()->addError('Item could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Item successfully deleted');
                }
            } else {
                $this->messenger()->addError('Item could not be deleted');
            }
        }
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'item',
            'action'     => 'browse',
        ));
    }

    public function addAction()
    {
        $view = new ViewModel;
        $form = new ResourceForm($this->getServiceLocator());
        $view->setVariable('form', $form);
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()) {
                $response = $this->api()->create('items', $data);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Item Created.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }
        return $view;
    }

    public function editAction()
    {
        $view = new ViewModel;
        $form = new ResourceForm($this->getServiceLocator());
        $view->setVariable('form', $form);
        $id = $this->params('id');
        $response = $this->api()->read('items', $id);
        $item = $response->getContent();
        $values = array();
        foreach ($item->values() as $vocabulary) {
            foreach ($vocabulary['properties'] as $property) {
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
                    }
                    $values[$property['property']->term()][] = $valuesArray;
                }
            }
        }
        
        $view->setVariable('item', $item);
        $view->setVariable('values', json_encode($values));
            if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()) {
                $response = $this->api()->update('items', $data);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Item Updated.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }
        return $view;
    }
}

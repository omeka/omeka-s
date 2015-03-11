<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ResourceForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ItemSetController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
    }

    public function searchAction()
    {
        $view = new ViewModel;
        return $view;
    }
    
    public function addAction()
    {
        $form = new ResourceForm($this->getServiceLocator());
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()) {
                $response = $this->api()->create('item_sets', $data);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('Item Set Created.');
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
        $form = new ResourceForm($this->getServiceLocator());
        $id = $this->params('id');
        $response = $this->api()->read('item_sets', $id);
        $itemSet = $response->getContent();
        $values = array();
        foreach ($itemSet->values() as $term => $property) {
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
        $view->setVariable('itemSet', $itemSet);
        $view->setVariable('values', json_encode($values));
            if ($this->getRequest()->isPost()) {
                $data = $this->params()->fromPost();
                $form->setData($data);
                if($form->isValid()) {
                    $response = $this->api()->update('item_sets', $id, $data);
                    if ($response->isError()) {
                        $form->setMessages($response->getErrors());
                    } else {
                        $this->messenger()->addSuccess('Item Set Updated.');
                        return $this->redirect()->toUrl($response->getContent()->url());
                    }
                } else {
                    $this->messenger()->addError('There was an error during validation');
                }
        }
        return $view;
    }
    
    public function browseAction()
    {
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('item_sets', $query);
        $this->paginator($response->getTotalResults(), $page);

        $view = new ViewModel;
        $view->setVariable('itemSets', $response->getContent());
        $view->setVariable('confirmForm', new ConfirmForm(
            $this->getServiceLocator(), null, array(
                'button_value' => $this->translate('Confirm Delete'),
            )
        ));
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read('item_sets', $this->params('id'));

        $view = new ViewModel;
        $view->setVariable('itemSet', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);
        $response = $this->api()->read('item_sets', $this->params('id'));
        $itemSet = $response->getContent();
        $view = new ViewModel;
        $view->setTerminal(true);
        $values = array('@id'               => $itemSet->id(),
                        'dcterms:title'     => $itemSet->displayTitle('[Untitled]'),
                        'url'               => $itemSet->url(),
                        'value_resource_id' => $itemSet->id()
                );
        
        $view->setVariable('linkTitle', $linkTitle);
        $view->setVariable('itemSet', $itemSet);
        $view->setVariable('values', json_encode($values));
        return $view;
    }

    public function sidebarSelectAction()
    {
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('item_sets', $query);
        $this->paginator($response->getTotalResults(), $page);

        $view = new ViewModel;
        $view->setVariable('itemSets', $response->getContent());
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
                $response = $this->api()->delete('item_sets', $this->params('id'));
                if ($response->isError()) {
                    $this->messenger()->addError('Item set could not be deleted');
                } else {
                    $this->messenger()->addSuccess('Item set successfully deleted');
                }
            } else {
                $this->messenger()->addError('Item set could not be deleted');
            }
        }
        return $this->redirect()->toRoute(null, array('action' => 'browse'), true);
    }
}

<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\ItemForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Form\Form;
use Zend\Form\Element\Csrf;

class ItemController extends AbstractActionController
{
    public function searchAction()
    {
        $view = new ViewModel;
        return $view;
    }

    public function browseAction()
    {
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        if (!isset($query['sort_by'])) {
            $query['sort_by'] = 'created';
            $query['sort_order'] = 'desc';
        }
        $response = $this->api()->search('items', $query);
        $this->paginator($response->getTotalResults(), $page);

        $view = new ViewModel;
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
        $response = $this->api()->read('items', $this->params('id'));

        $view = new ViewModel;
        $view->setVariable('item', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $linkTitle = (bool) $this->params()->fromQuery('link-title', true);
        $response = $this->api()->read('items', $this->params('id'));
        $item = $response->getContent();
        $values = array();
        //create a value that matches what comes to the edit form for internal resources
        //so this can be used the same way in the sidebar with makeNewValue
        $values = array('@id'               => $item->id(),
                        'dcterms:title'     => $item->displayTitle('[Untitled]'),
                        'url'               => $item->url(),
                        'value_resource_id' => $item->id()
                );

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('linkTitle', $linkTitle);
        $view->setVariable('item', $item);
        $view->setVariable('values', json_encode($values));
        return $view;
    }
    
    public function sidebarSelectAction()
    {
        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('items', $query);
        $this->paginator($response->getTotalResults(), $page);

        $view = new ViewModel;
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
        return $this->redirect()->toRoute(
            'admin/default',
            array('action' => 'browse'),
            true
        );
    }

    public function addAction()
    {
        $form = new ItemForm($this->getServiceLocator());
        $form->setAttribute('id', 'add-item');
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()) {
                $fileData = $this->getRequest()->getFiles()->toArray();
                $response = $this->api()->create('items', $data, $fileData);
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

        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('mediaForms', $this->getMediaForms());
        return $view;
    }

    public function editAction()
    {
        $form = new ItemForm($this->getServiceLocator());
        $form->setAttribute('id', 'edit-item');
        $id = $this->params('id');
        $response = $this->api()->read('items', $id);
        $item = $response->getContent();
        $values = array();
        foreach ($item->values() as $term => $property) {
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

        $form->get('o:item_set')->setValue(array_keys($item->itemSets()));
        
        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('item', $item);
        $view->setVariable('mediaForms', $this->getMediaForms());
        $view->setVariable('values', json_encode($values));
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if($form->isValid()) {
                $fileData = $this->getRequest()->getFiles()->toArray();
                $response = $this->api()->update('items', $id, $data, $fileData);
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

    protected function getMediaForms()
    {
        $services = $this->getServiceLocator();
        $mediaHelper = $services->get('ViewHelperManager')->get('media');
        $mediaManager = $services->get('Omeka\MediaHandlerManager');
        $types = $mediaManager->getCanonicalNames();

        $forms = array();
        foreach ($types as $type) {
            $forms[$type] = array(
                'label' => $mediaManager->get($type)->getLabel(),
                'form' => $mediaHelper->form($type)
            );
        }

        return $forms;
    }
}

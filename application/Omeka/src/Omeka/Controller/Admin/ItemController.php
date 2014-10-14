<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\ResponseFilter;
use Omeka\Form\ItemForm;
use Omeka\Form\DeleteForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Form\Form;

class ItemController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'item',
            'action' => 'browse',
        ));
    }

    public function browseAction()
    {
        $view = new ViewModel;

        $page = $this->params()->fromQuery('page', 1);
        $query = $this->params()->fromQuery() + array('page' => $page);
        $response = $this->api()->search('items', $query);
        if ($response->isError()) {
            $this->apiError($response);
            return;
        }

        $this->paginator($response->getTotalResults(), $page);
        $view->setVariable('items', $response->getContent());
        $view->setVariable('deleteForm', new DeleteForm);
        return $view;
    }

    public function showAction()
    {
        $view = new ViewModel;
        $id = $this->params('id');
        $response = $this->api()->read('items', $id);
        if ($response->isError()) {
            $this->apiError($response);
            return;
        }
        $view->setVariable('item', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $response = $this->api()->read(
            'items', array('id' => $this->params('id'))
        );
        if ($response->isError()) {
            $this->apiError($response);
            return;
        }
        $view->setVariable('item', $response->getContent());
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new DeleteForm;
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
        $response = $this->api()->search('resource_classes');
        $resourceClasses = $response->getContent();
        $resourceClassPairs = array();
        foreach ($resourceClasses as $resourceClass) {
            $resourceClassPairs[$resourceClass->id()] = $resourceClass->label();
        }

        $dctermsTitles = $this->api()
                              ->search('properties', array('term' => 'dcterms:title'))
                              ->getContent();
        $dctermsDescriptions = $this->api()
                              ->search('properties', array('term' => 'dcterms:description'))
                              ->getContent();
        $options = array(
            'resource_class_pairs' => $resourceClassPairs,
            'dcterms_title'        => $dctermsTitles[0],
            'dcterms_description'  => $dctermsDescriptions[0]
            );
        $form = new ItemForm('items', $options);
        $view->setVariable('form', $form);

        $vocabularies = $this->getVocabularies();
        $view->setVariable('vocabularies', $vocabularies);

        if ($this->getRequest()->isPost()) {
            $response = $this->api()->create('items', $this->params()->fromPost());
            if ($response->isError()) {
                $view->setVariable('errors', $response->getErrors());
            } else {
                $view->setVariable('item', $response->getContent());
            }
        }
        return $view;
    }

    public function editAction()
    {}

    protected function getVocabularies()
    {
        $vocabulariesArray = array();
        $response = $this->api()->search('vocabularies');
        $vocabularies = $response->getContent();
        foreach ($vocabularies as $vocabulary) {
            $prefix = $vocabulary->prefix();
            $properties = $this->api()->search('properties', 
                array('vocabulary_prefix' => $vocabulary->prefix()))->getContent();
            $label = $vocabulary->label();
            $vocabulariesArray[$label] = array();
            foreach ($properties as $property) {
                $vocabulariesArray[$label][] = array(
                    'id'      => $property->id(), 
                    'label'   => $property->label(),
                    'comment' => $property->comment(),
                    'qname'   => $prefix . ':' . $property->localName()
                    );
            }
        }
        return $vocabulariesArray;
    }
}

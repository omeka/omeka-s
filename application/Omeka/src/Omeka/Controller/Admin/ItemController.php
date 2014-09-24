<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\ResponseFilter;
use Omeka\Form\ItemForm;
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
        $this->paginator($response->getTotalResults(), $page);

        $items = $response->getContent();
        $view->setVariable('items', $items);
        return $view;
    }

    public function addAction()
    {
        $view = new ViewModel;
        $response = $this->api()->search('resource_classes');
        $resourceClasses = $response->getContent();
        $resourceClassPairs = array();
        foreach ($resourceClasses as $resourceClass) {
            $resourceClassPairs[$resourceClass->getId()] = $resourceClass->getLabel();
        }
        $response = $this->api()->search('properties');
        $properties = $response->getContent();
        $dctermsTitles = $this->api()
                              ->search('properties', array('vocabulary_prefix' => 'dcterms', 
                                                           'local_name' => 'title'))
                              ->getContent();
        $dctermsDescriptions = $this->api()
                              ->search('properties', array('vocabulary_prefix' => 'dcterms', 
                                                           'local_name' => 'description'))
                              ->getContent();
        $options = array(
                'resource_class_pairs' => $resourceClassPairs,
                'properties'           => $properties,
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

    /**
     * Create new form inputs for ajaxing into the item form
     * @see ItemForm
     */
    public function ajaxPropertyElementAction()
    {
        $view = new ViewModel();
        $view->setTerminal(true);
        $propertyId = $this->params()->fromQuery('id');
        $response = $this->api()->search('properties', array('id' => $propertyId));
        $properties = $response->getContent();
        $property = $properties[0];
        $form = new Form;
        //the following duplicates ItemForm->addPropertyInput
        $qName = $property->getVocabulary()->getPrefix() . ':' . $property->getLocalName();
        $form->add(array(
                'name'    => $qName . '[0][@value]',
                'type'    => 'Text',
                'options' => array(
                    'label' => $property->getLabel()
                )
        ));

        $form->add(array(
                'name'       => $qName . '[0][property_id]',
                'type'       => 'Hidden',
                'attributes' => array(
                    'value' => $property->getId()
                )
        ));        
        $view->setVariable('form', $form);
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
            $properties = $this->api()->search('properties', 
                array('vocabulary_prefix' => $vocabulary->getPrefix()))->getContent();
            $label = $vocabulary->getLabel();
            $vocabulariesArray[$label] = array();
            foreach ($properties as $property) {
                $vocabulariesArray[$label][] = array(
                        'id'          => $property->getId(), 
                        'label'       => $property->getLabel(),
                        'comment' => $property->getComment()
                        );
            }
        }
        return $vocabulariesArray;
    }
}

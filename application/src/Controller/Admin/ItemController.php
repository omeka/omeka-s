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
        $view->setVariable('deleteForm', new DeleteForm($this->getServiceLocator()));
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

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = new DeleteForm($this->getServiceLocator());
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
        $properties = array($dctermsTitles[0], $dctermsDescriptions[0]);
        $options = array(
            'resource_class_pairs' => $resourceClassPairs,
            'properties'           => $properties
            );
        $form = new ItemForm('items', $options);
        $view->setVariable('form', $form);

        /* PMJ temporary hack to have some items in the sidebar */
        $items = $this->api()->search('items')->getContent();
        $view->setVariable('items', $items);
        /* end PMJ hack */
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
}

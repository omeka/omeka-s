<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\ResponseFilter;
use Omeka\Form\DeleteForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

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
            'action' => 'browse',
        ));
    }

    public function addAction()
    {}

    public function editAction()
    {}
}

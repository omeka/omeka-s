<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\ResponseFilter;
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
            $response = $this->api()->delete(
                'items', array('id' => $this->params('id'))
            );
            if ($this->apiError($response)) {
                return;
            }
            $this->messenger()->addSuccess('Item successfully deleted');
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

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
        $response = $this->api()->search('items', array('page' => $page));
        $items = $response->getContent();

        $this->paginator($response->getTotalResults(), $page);

        $view->setVariable('items', $items);
        return $view;
    }

    public function addAction()
    {}

    public function editAction()
    {}
}

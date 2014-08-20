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
        $response = $this->api()->search('items', array('limit' => 2));
        $items = $response->getContent();

        $this->paginator(
            $response->getTotalResults(),
            $this->params()->fromQuery('page', 1)
        );

        $view->setVariable('items', $items);
        return $view;
    }

    public function addAction()
    {}

    public function editAction()
    {}
}

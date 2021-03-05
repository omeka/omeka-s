<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Exception\ValidationException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class QueryController extends AbstractActionController
{
    public function sidebarEditAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        return $view;
    }

    public function sidebarPreviewAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('items', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults());

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('items', $response->getContent());
        return $view;
    }
}

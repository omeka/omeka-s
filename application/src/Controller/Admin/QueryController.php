<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\Exception\ValidationException;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Laminas\View\Model\ViewModel;
use Laminas\Mvc\Controller\AbstractActionController;

class QueryController extends AbstractActionController
{
    public function sidebarSearchAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        return $view;
    }
}

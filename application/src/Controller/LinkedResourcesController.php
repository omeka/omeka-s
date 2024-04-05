<?php
namespace Omeka\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;


class LinkedResourcesController extends AbstractActionController
{
    public function __construct()
    {}

    public function indexAction()
    {
        $resource = $this->api()->read('resources', $this->params('resource-id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $resource);
        return $view;
    }
}

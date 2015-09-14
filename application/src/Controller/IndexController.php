<?php
namespace Omeka\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        $response = $this->api()->search('sites');
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $this->layout('layout/minimal');

        $view = new ViewModel;
        $view->setVariable('sites', $response->getContent());
        return $view;
    }
}

<?php
namespace Omeka\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PropertyController extends AbstractActionController
{
    public function showDetailsAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $response = $this->api()->read(
            'properties', array('id' => $this->params('id'))
        );
        $view->setVariable('property', $response->getContent());
        return $view;
    }
}

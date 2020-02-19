<?php
namespace Omeka\Controller\Admin;

use Omeka\Mvc\Exception;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class PropertyController extends AbstractActionController
{
    public function showDetailsAction()
    {
        if (!$this->params('id')) {
            throw new Exception\NotFoundException;
        }

        $response = $this->api()->read('properties', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('property', $response->getContent());
        return $view;
    }
}

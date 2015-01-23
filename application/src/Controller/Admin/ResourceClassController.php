<?php
namespace Omeka\Controller\Admin;

use Omeka\Mvc\Exception;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ResourceClassController extends AbstractActionController
{
    public function showDetailsAction()
    {
        if (!$this->params('id')) {
            throw new Exception\NotFoundException;
        }

        $response = $this->api()->read('resource_classes', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resourceClass', $response->getContent());
        return $view;
    }
}

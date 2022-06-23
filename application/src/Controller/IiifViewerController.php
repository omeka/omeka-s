<?php
namespace Omeka\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IiifViewerController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('url', $this->params()->fromQuery('url'));
        return $view;
    }
}

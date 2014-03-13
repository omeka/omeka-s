<?php
namespace Omeka\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    public function indexAction()
    {}

    public function addAction()
    {
        $view = new ViewModel;
        if ($this->getRequest()->isPost()) {
            $response = $this->api()->create('users', $this->params()->fromPost());
            if ($response->isError()) {
                $view->setVariable('errors', $response->getErrors());
            } else {
                $view->setVariable('user', $response->getContent());
            }
        }
        return $view;
    }

    public function browseAction()
    {
        $view = new ViewModel;
        $response = $this->api()->search('users', array());
        if ($response->isError()) {
            $view->setVariable('errors', $response->getErrors());
        } else {
            $view->setVariable('users', $response->getContent());
        }
        return $view;
    }
}

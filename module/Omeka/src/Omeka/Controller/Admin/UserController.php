<?php
namespace Omeka\Controller\Admin;

use Omeka\Api\ResponseFilter;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    public function indexAction()
    {}

    public function addAction()
    {
        $api = $this->getServiceLocator()->get('ApiManager');
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();
            $response = $api->create('users', $data);
            if ($response->isError()) {
                print_r($response->getErrors());
            }
            $user = $response->getContent();
            return new ViewModel(array('user' => $user));
        }
    }

    public function browseAction()
    {
        $api = $this->getServiceLocator()->get('ApiManager');
        $filter = new ResponseFilter();
        $response = $api->search('users', array());
        $users = $response->getContent();
        return new ViewModel(array('users'=>$users));
    }
}
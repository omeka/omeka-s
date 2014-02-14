<?php
namespace Omeka\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

/**
 * Simple login and user status controller.
 */
class LoginController extends AbstractActionController
{
    public function indexAction()
    {
        $model = array();
        $auth = $this->getServiceLocator()->get('AuthenticationService');

        if ($this->getRequest()->isPost()) {
            $username = $this->params()->fromPost('username');
            $password = $this->params()->fromPost('password');

            $adapter = $auth->getAdapter();
            $adapter->setIdentity($username);
            $adapter->setCredential($password);

            $result = $auth->authenticate();
            if (!$result->isValid()) {
                $model['messages'] = $result->getMessages();
            }
        }

        $model['user'] = $auth->getIdentity();

        return new ViewModel($model);
    }
}

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
        $view = new ViewModel;
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $flashMessenger = $this->flashMessenger();

        if ($this->getRequest()->isPost()) {

            switch ($this->params()->fromPost('action')) {
                case 'login':
                    $adapter = $auth->getAdapter();
                    $adapter->setIdentity($this->params()->fromPost('username'));
                    $adapter->setCredential($this->params()->fromPost('password'));
                    $result = $auth->authenticate();
                    if ($result->isValid()) {
                        $flashMessenger->addSuccessMessage('Successfully logged in');
                    } else {
                        $flashMessenger->addErrorMessage('Username or password is invalid');
                    }
                    break;
                case 'logout':
                    $auth->clearIdentity();
                    $flashMessenger->addSuccessMessage('Successfully logged out');
                    break;
                default:
                    break;
            }
            return $this->redirect()->refresh();
        }

        if ($flashMessenger->hasErrorMessages()) {
            $view->setVariable(
                'errorMessages',
                $flashMessenger->getErrorMessages()
            );
        }
        if ($flashMessenger->hasSuccessMessages()) {
            $view->setVariable(
                'successMessages',
                $flashMessenger->getSuccessMessages()
            );
        }

        $view->setVariable('auth', $auth);
        $view->setVariable('user', $this->identity());
        return $view;
    }
}

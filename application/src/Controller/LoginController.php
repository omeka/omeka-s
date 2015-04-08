<?php
namespace Omeka\Controller;

use Omeka\Form\LoginForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class LoginController extends AbstractActionController
{
    public function loginAction()
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        if ($auth->hasIdentity()) {
            return $this->redirect()->toRoute('admin');
        }

        $form = new LoginForm($this->getServiceLocator());

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $validatedData = $form->getData();
                $adapter = $auth->getAdapter();
                $adapter->setIdentity($validatedData['username']);
                $adapter->setCredential($validatedData['password']);
                $result = $auth->authenticate();
                if ($result->isValid()) {
                    $this->messenger()->addSuccess('Successfully logged in');
                    return $this->redirect()->toRoute('admin/default');
                } else {
                    $this->messenger()->addError('Username or password is invalid');
                }
            } else {
                $this->messenger()->addError('Username or password is invalid');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function logoutAction()
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->clearIdentity();
        $this->messenger()->addSuccess('Successfully logged out');
        return $this->redirect()->toRoute('login');
    }
}

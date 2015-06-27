<?php
namespace Omeka\Controller;

use Omeka\Form\LoginForm;
use Omeka\Form\ActivateForm;
use Omeka\Form\ResetPasswordForm;
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
                $adapter->setIdentity($validatedData['email']);
                $adapter->setCredential($validatedData['password']);
                $result = $auth->authenticate();
                if ($result->isValid()) {
                    $this->messenger()->addSuccess('Successfully logged in');
                    return $this->redirect()->toRoute('admin/default');
                } else {
                    $this->messenger()->addError('Email or password is invalid');
                }
            } else {
                $this->messenger()->addError('Email or password is invalid');
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

    public function activateAction()
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $userActivation = $entityManager->find(
            'Omeka\Entity\UserActivation',
            $this->params('key')
        );
        if (!$userActivation) {
            $this->messenger()->addError('Invalid activation key.');
            return $this->redirect()->toRoute('login');
        }

        $form = new ActivateForm($this->getServiceLocator());

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $userActivation->getUser()->setPassword($data['password']);
                $entityManager->remove($userActivation);
                $entityManager->flush();
                $this->getServiceLocator()->get('Omeka\AuthenticationService')
                    ->clearIdentity();
                $this->messenger()->addSuccess('Successfully activated your account. Please log in.');
                return $this->redirect()->toRoute('login');
            } else {
                $this->messenger()->addError('Activation unsuccessful');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function resetPasswordAction()
    {
        $form = new ResetPasswordForm($this->getServiceLocator());

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }
}

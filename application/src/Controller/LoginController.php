<?php
namespace Omeka\Controller;

use DateInterval;
use DateTime;
use Omeka\Form\LoginForm;
use Omeka\Form\ActivateForm;
use Omeka\Form\ForgotPasswordForm;
use Omeka\Form\ResetPasswordForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
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
                $sessionManager = Container::getDefaultManager();
                $sessionManager->regenerateId();
                $validatedData = $form->getData();
                $adapter = $auth->getAdapter();
                $adapter->setIdentity($validatedData['email']);
                $adapter->setCredential($validatedData['password']);
                $result = $auth->authenticate();
                if ($result->isValid()) {
                    $this->messenger()->addSuccess('Successfully logged in');
                    $redirectUrl = $this->params()->fromQuery('redirect');
                    if ($redirectUrl) {
                        return $this->redirect()->toUrl($redirectUrl);
                    }
                    return $this->redirect()->toRoute('admin/default');
                } else {
                    $this->messenger()->addError('Email or password is invalid');
                }
            } else {
                $this->messenger()->addError('Email or password is invalid');
            }
        }

        $this->layout('layout/minimal');
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function logoutAction()
    {
        $auth = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        $auth->clearIdentity();
        $sessionManager = Container::getDefaultManager();
        $sessionManager->destroy();
        $this->messenger()->addSuccess('Successfully logged out');
        return $this->redirect()->toRoute('login');
    }

    public function createPasswordAction()
    {
        $authentication = $this->getServiceLocator()->get('Omeka\AuthenticationService');
        if ($authentication->hasIdentity()) {
            return $this->redirect()->toRoute('admin');
        }

        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $passwordCreation = $entityManager->find(
            'Omeka\Entity\PasswordCreation',
            $this->params('key')
        );

        if (!$passwordCreation) {
            $this->messenger()->addError('Invalid password creation key.');
            return $this->redirect()->toRoute('login');
        }
        $user = $passwordCreation->getUser();

        if (new DateTime > $passwordCreation->getExpiration()) {
            $user->setIsActive(false);
            $entityManager->remove($passwordCreation);
            $entityManager->flush();
            $this->messenger()->addError('Password creation key expired.');
            return $this->redirect()->toRoute('login');
        }

        $form = new ActivateForm($this->getServiceLocator());

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $user->setPassword($data['password']);
                if ($passwordCreation->activate()) {
                    $user->setIsActive(true);
                }
                $entityManager->remove($passwordCreation);
                $entityManager->flush();
                $this->messenger()->addSuccess('Successfully created your Omeka S password. Please log in.');
                return $this->redirect()->toRoute('login');
            } else {
                $this->messenger()->addError('Password creation unsuccessful');
            }
        }

        $this->layout('layout/minimal');
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function forgotPasswordAction()
    {
        $serviceLocator = $this->getServiceLocator();
        $authentication = $serviceLocator->get('Omeka\AuthenticationService');
        if ($authentication->hasIdentity()) {
            return $this->redirect()->toRoute('admin');
        }

        $form = new ForgotPasswordForm($serviceLocator);

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $entityManager = $serviceLocator->get('Omeka\EntityManager');
                $user =  $entityManager->getRepository('Omeka\Entity\User')
                    ->findOneBy(array(
                        'email' => $data['email'],
                        'isActive' => true,
                    ));
                if ($user) {
                    $passwordCreation = $entityManager
                        ->getRepository('Omeka\Entity\PasswordCreation')
                        ->findOneBy(array('user' => $user));
                    if ($passwordCreation) {
                        $entityManager->remove($passwordCreation);
                        $entityManager->flush();
                    }
                    $serviceLocator->get('Omeka\Mailer')->sendResetPassword($user);
                }
                $this->messenger()->addSuccess('Check your email for instructions on how to reset your password');
                return $this->redirect()->toRoute('login');
            } else {
                $this->messenger()->addError('Activation unsuccessful');
            }
        }

        $this->layout('layout/minimal');
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }
}

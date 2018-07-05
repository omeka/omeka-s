<?php
namespace Omeka\Controller;

use DateTime;
use Doctrine\ORM\EntityManager;
use Omeka\Form\LoginForm;
use Omeka\Form\ActivateForm;
use Omeka\Form\ForgotPasswordForm;
use Zend\Authentication\AuthenticationService;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;

class LoginController extends AbstractActionController
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var AuthenticationService
     */
    protected $auth;

    /**
     * @param EntityManager $entityManager
     * @param AuthenticationService $auth
     */
    public function __construct(EntityManager $entityManager, AuthenticationService $auth)
    {
        $this->entityManager = $entityManager;
        $this->auth = $auth;
    }

    public function loginAction()
    {
        if ($this->auth->hasIdentity()) {
            return $this->redirect()->toRoute('admin');
        }

        $form = $this->getForm(LoginForm::class);

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $sessionManager = Container::getDefaultManager();
                $sessionManager->regenerateId();
                $validatedData = $form->getData();
                $adapter = $this->auth->getAdapter();
                $adapter->setIdentity($validatedData['email']);
                $adapter->setCredential($validatedData['password']);
                $result = $this->auth->authenticate();
                if ($result->isValid()) {
                    $this->messenger()->addSuccess('Successfully logged in'); // @translate
                    $eventManager = $this->getEventManager();
                    $eventManager->trigger('user.login', $this->auth->getIdentity());
                    $session = $sessionManager->getStorage();
                    if ($redirectUrl = $session->offsetGet('redirect_url')) {
                        return $this->redirect()->toUrl($redirectUrl);
                    }
                    return $this->redirect()->toRoute('admin');
                } else {
                    $this->messenger()->addError('Email or password is invalid'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function logoutAction()
    {
        $this->auth->clearIdentity();

        $sessionManager = Container::getDefaultManager();

        $eventManager = $this->getEventManager();
        $eventManager->trigger('user.logout');

        $sessionManager->destroy();

        $this->messenger()->addSuccess('Successfully logged out'); // @translate
        return $this->redirect()->toRoute('login');
    }

    public function createPasswordAction()
    {
        if ($this->auth->hasIdentity()) {
            return $this->redirect()->toRoute('admin');
        }

        $passwordCreation = $this->entityManager->find(
            'Omeka\Entity\PasswordCreation',
            $this->params('key')
        );

        if (!$passwordCreation) {
            $this->messenger()->addError('Invalid password creation key.'); // @translate
            return $this->redirect()->toRoute('login');
        }
        $user = $passwordCreation->getUser();

        if (new DateTime > $passwordCreation->getExpiration()) {
            $user->setIsActive(false);
            $this->entityManager->remove($passwordCreation);
            $this->entityManager->flush();
            $this->messenger()->addError('Password creation key expired.'); // @translate
            return $this->redirect()->toRoute('login');
        }

        $form = $this->getForm(ActivateForm::class);

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $user->setPassword($data['password']);
                if ($passwordCreation->activate()) {
                    $user->setIsActive(true);
                }
                $this->entityManager->remove($passwordCreation);
                $this->entityManager->flush();
                $this->messenger()->addSuccess('Successfully created your password. Please log in.'); // @translate
                return $this->redirect()->toRoute('login');
            } else {
                $this->messenger()->addError('Password creation unsuccessful'); // @translate
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function forgotPasswordAction()
    {
        if ($this->auth->hasIdentity()) {
            return $this->redirect()->toRoute('admin');
        }

        $form = $this->getForm(ForgotPasswordForm::class);

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                $user = $this->entityManager->getRepository('Omeka\Entity\User')
                    ->findOneBy([
                        'email' => $data['email'],
                        'isActive' => true,
                    ]);
                if ($user) {
                    $passwordCreation = $this->entityManager
                        ->getRepository('Omeka\Entity\PasswordCreation')
                        ->findOneBy(['user' => $user]);
                    if ($passwordCreation) {
                        $this->entityManager->remove($passwordCreation);
                        $this->entityManager->flush();
                    }
                    $this->mailer()->sendResetPassword($user);
                }
                $this->messenger()->addSuccess('Check your email for instructions on how to reset your password'); // @translate
                return $this->redirect()->toRoute('login');
            } else {
                $this->messenger()->addError('Activation unsuccessful'); // @translate
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }
}

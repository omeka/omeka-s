<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\UserForm;
use Omeka\Form\UserPasswordForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'user',
            'action' => 'browse',
        ));
    }

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

    public function showAction()
    {
        $view = new ViewModel;
        $id = $this->params('id');

        $response = $this->api()->read('users', $id);
        if (!$this->apiError($response)) {
            $view->setVariable('user', $response->getContent());
        }
        return $view;
    }

    public function editAction()
    {
        $view = new ViewModel;
        $form = new UserForm;
        $id = $this->params('id');

        $readResponse = $this->api()->read('users', $id);
        if ($this->apiError($readResponse)) {
            return;
        }
        $user = $readResponse->getContent();
        $data = $user->jsonSerialize();
        $form->setData($data);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $response = $this->api()->update('users', $id, $formData);
                if (!$this->apiError($response)) {
                    $this->messenger()->addSuccess('User updated.');
                    return $this->redirect()->refresh();
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view->setVariable('user', $user);
        $view->setVariable('form', $form);
        return $view;
    }

    public function changePasswordAction()
    {
        $view = new ViewModel;
        $form = new UserPasswordForm;
        $id = $this->params('id');

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $user = $em->find('Omeka\Model\Entity\User', $id);
        if (!$user) {
            $this->getResponse()->setStatusCode(404);
            return $view;
        }

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $values = $form->getData();
                $user->setPassword($values['password']);
                $em->flush();
                $this->messenger()->addSuccess('Password changed.');
                return $this->redirect()->toRoute(null, array('action' => 'edit'), array(), true);
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view->setVariable('user', $user);
        $view->setVariable('form', $form);
        return $view;
    }
}

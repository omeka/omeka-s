<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\UserForm;
use Omeka\Form\UserKeyForm;
use Omeka\Form\UserPasswordForm;
use Omeka\Entity\ApiKey;
use Zend\Mvc\Controller\AbstractActionController;
use Omeka\Mvc\Exception;
use Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    public function addAction()
    {   
        $changeRole = $this->getServiceLocator()->get('Omeka\Acl')->userIsAllowed('Omeka\Entity\User', 'change-role');
        $form = new UserForm($this->getServiceLocator(), null, array('include_role' => $changeRole));
        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $response = $this->api()->create('users', $formData);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('User created.');
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('email');
        $response = $this->api()->search('users', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $view->setVariable('users', $response->getContent());
        return $view;
    }

    public function showAction()
    {
        $response = $this->api()->read('users', $this->params('id'));

        $view = new ViewModel;
        $view->setVariable('user', $response->getContent());
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('users', $this->params('id'));

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('user', $response->getContent());
        return $view;
    }

    public function editAction()
    {
        $id = $this->params('id');

        $readResponse = $this->api()->read('users', $id);
        $user = $readResponse->getContent();
        $changeRole = $this->getServiceLocator()->get('Omeka\Acl')->userIsAllowed($user->getEntity(), 'change-role');
        $form = new UserForm($this->getServiceLocator(), null, array('include_role' => $changeRole));
        $data = $user->jsonSerialize();
        $form->setData($data);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $response = $this->api()->update('users', $id, $formData, array(), true);
                if ($response->isError()) {
                    $form->setMessages($response->getErrors());
                } else {
                    $this->messenger()->addSuccess('User updated.');
                    return $this->redirect()->refresh();
                }
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        $view = new ViewModel;
        $view->setVariable('user', $user);
        $view->setVariable('form', $form);
        return $view;
    }

    public function changePasswordAction()
    {
        $form = new UserPasswordForm($this->getServiceLocator());
        $id = $this->params('id');

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $readResponse = $this->api()->read('users', $id);
        $userRepresentation = $readResponse->getContent();
        $user = $userRepresentation->getEntity();

        if ($this->getRequest()->isPost()) {
            $acl = $this->getServiceLocator()->get('Omeka\Acl');
            if (!$acl->userIsAllowed($user, 'change-password')) {
                throw new Exception\PermissionDeniedException(
                    'User does not have permission to change the password'
                );
            }
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

        $view = new ViewModel;
        $view->setVariable('user', $userRepresentation);
        $view->setVariable('form', $form);
        return $view;
    }

    public function editKeysAction()
    {
        $form = new UserKeyForm($this->getServiceLocator());
        $id = $this->params('id');

        $em = $this->getServiceLocator()->get('Omeka\EntityManager');
        $readResponse = $this->api()->read('users', $id);
        $userRepresentation = $readResponse->getContent();
        $user = $userRepresentation->getEntity();
        $keys = $user->getKeys();

        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                $formData = $form->getData();
                $this->addKey($em, $user, $formData['new-key-label']);

                // Remove any keys marked for deletion
                if (!empty($postData['delete']) && is_array($postData['delete'])) {
                    foreach ($postData['delete'] as $deleteId) {
                        $keys->remove($deleteId);
                    }
                    $this->messenger()->addSuccess("Deleted key(s).");
                }
                $em->flush();
                return $this->redirect()->refresh();
            } else {
                $this->messenger()->addError('There was an error during validation');
            }
        }

        // Only expose key IDs and values to the view
        $viewKeys = array();
        foreach ($keys as $id => $key) {
            $viewKeys[$id] = $key->getLabel();
        }

        $view = new ViewModel;
        $view->setVariable('user', $userRepresentation);
        $view->setVariable('keys', $viewKeys);
        $view->setVariable('form', $form);
        return $view;
    }

    private function addKey($em, $user, $label)
    {
        if (empty($label)) {
            return;
        }

        $key = new ApiKey;
        $key->setId();
        $key->setLabel($label);
        $key->setOwner($user);
        $id = $key->getId();
        $credential = $key->setCredential();
        $em->persist($key);

        $this->messenger()->addSuccess('Key created.');
        $this->messenger()->addSuccess("ID: $id, Credential: $credential");
    }
}

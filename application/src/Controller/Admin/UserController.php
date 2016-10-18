<?php
namespace Omeka\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Omeka\Entity\ApiKey;
use Omeka\Entity\User;
use Omeka\Form\ConfirmForm;
use Omeka\Form\UserForm;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\Message;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class UserController extends AbstractActionController
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addAction()
    {
        $changeRole = $this->userIsAllowed('Omeka\Entity\User', 'change-role');
        $changeRoleAdmin = $this->userIsAllowed('Omeka\Entity\User', 'change-role-admin');
        $activateUser = $this->userIsAllowed('Omeka\Entity\User', 'activate-user');

        $form = $this->getForm(UserForm::class, [
            'include_role' => $changeRole,
            'include_admin_roles' => $changeRoleAdmin,
            'include_is_active' => $activateUser,
        ]);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->params()->fromPost());
            if ($form->isValid()) {
                $formData = $form->getData();
                $response = $this->api($form)->create('users', $formData);
                if ($response->isSuccess()) {
                    $user = $response->getContent()->getEntity();
                    $this->mailer()->sendUserActivation($user);
                    $message = new Message(
                        'User successfully created. %s', // @translate
                        sprintf(
                            '<a href="%s">%s</a>',
                            htmlspecialchars($this->url()->fromRoute(null, [], true)),
                            $this->translate('Add another user?')
                        ));
                    $message->setEscapeHtml(false);
                    $this->messenger()->addSuccess($message);
                    return $this->redirect()->toUrl($response->getContent()->url());
                }
            } else {
                $this->messenger()->addErrors($form->getMessages());
            }
        }

        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }

    public function browseAction()
    {
        $this->setBrowseDefaults('email', 'asc');
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
        $view->setVariable('resource', $response->getContent());
        return $view;
    }

    public function deleteConfirmAction()
    {
        $resource = $this->api()->read('users', $this->params('id'))->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('resource', $resource);
        $view->setVariable('resourceLabel', 'user');
        $view->setVariable('partialPath', 'omeka/admin/user/show-details');
        return $view;
    }

    public function editAction()
    {
        $id = $this->params('id');

        $readResponse = $this->api()->read('users', $id);
        $user = $readResponse->getContent();
        $userEntity = $user->getEntity();
        $currentUser = $userEntity === $this->identity();
        $keys = $userEntity->getKeys();

        $changeRole = $this->userIsAllowed($userEntity, 'change-role');
        $changeRoleAdmin = $this->userIsAllowed($userEntity, 'change-role-admin');
        $activateUser = $this->userIsAllowed($userEntity, 'activate-user');

        $form = $this->getForm(UserForm::class, [
            'include_role' => $changeRole,
            'include_admin_roles' => $changeRoleAdmin,
            'include_is_active' => $activateUser,
            'current_password' => $currentUser,
        ]);

        $data = $user->jsonSerialize();
        $form->get('user-information')->populateValues($data);
        $form->get('change-password')->populateValues($data);

        // Only expose key IDs and values to the view
        $viewKeys = [];
        foreach ($keys as $keyId => $key) {
            $viewKeys[$keyId] = $key->getLabel();
        }

        $view = new ViewModel;
        $view->setVariable('user', $user);
        $view->setVariable('form', $form);
        $view->setVariable('keys', $viewKeys);

        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                $values = $form->getData();
                $passwordValues = $values['change-password'];
                $response = $this->api($form)->update('users', $id, $values['user-information']);
                if (!empty($passwordValues['password'])) {
                    if (!$this->userIsAllowed($userEntity, 'change-password')) {
                        throw new Exception\PermissionDeniedException(
                            'User does not have permission to change the password'
                        );
                    }
                    if ($currentUser && !$userEntity->verifyPassword($passwordValues['current-password'])) {
                        $this->messenger()->addError('The current password entered was invalid'); // @translate
                        return $view;
                    }
                    $userEntity->setPassword($passwordValues['password']);
                }
                if (!empty($values['edit-keys']['new-key-label']) || !empty($postData['delete'])) {
                    if (!$this->userIsAllowed($userEntity, 'edit-keys')) {
                        throw new Exception\PermissionDeniedException(
                            'User does not have permission to edit API keys'
                        );
                    }
                    $this->addKey($userEntity, $values['edit-keys']['new-key-label']);

                    // Remove any keys marked for deletion
                    if (!empty($postData['delete']) && is_array($postData['delete'])) {
                        foreach ($postData['delete'] as $deleteId) {
                            $keys->remove($deleteId);
                        }
                        $this->messenger()->addSuccess("Key(s) successfully deleted"); // @translate
                    }
                }
                if ($response->isSuccess()) {
                    $this->entityManager->flush();
                    $this->messenger()->addSuccess('User successfully updated'); // @translate
                    return $this->redirect()->refresh();
                }
            } else {
                $this->messenger()->addErrors($form->getMessages());
            }
        }
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('users', $this->params('id'));
                if ($response->isSuccess()) {
                    $this->messenger()->addSuccess('User successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addErrors($form->getMessages());
            }
        }
        return $this->redirect()->toRoute(
            'admin/default',
            ['action' => 'browse'],
            true
        );
    }

    private function addKey($user, $label)
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
        $this->entityManager->persist($key);

        $this->messenger()->addSuccess('Key created.'); // @translate
        $this->messenger()->addSuccess(new Message('ID: %s, Credential: %s', $id, $credential)); // @translate
    }
}

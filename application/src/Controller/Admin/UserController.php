<?php
namespace Omeka\Controller\Admin;

use Doctrine\ORM\EntityManager;
use Omeka\Entity\ApiKey;
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
                $response = $this->api($form)->create('users', $formData['user-information']);
                if ($response) {
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
                $this->messenger()->addFormErrors($form);
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
        $view->setVariable('resourceLabel', 'user'); // @translate
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
            'user_id' => $id,
            'include_role' => $changeRole,
            'include_admin_roles' => $changeRoleAdmin,
            'include_is_active' => $activateUser,
            'current_password' => $currentUser,
            'include_password' => true,
            'include_key' => true,
        ]);
        $form->setAttribute('action', $this->getRequest()->getRequestUri());

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

        $successMessages = [];

        if ($this->getRequest()->isPost()) {
            $postData = $this->params()->fromPost();
            $form->setData($postData);
            if ($form->isValid()) {
                $values = $form->getData();
                $passwordValues = $values['change-password'];
                $response = $this->api($form)->update('users', $id, $values['user-information']);

                // Stop early if the API update fails
                if (!$response) {
                    return $view;
                }
                $this->messenger()->addSuccess('User successfully updated'); // @translate

                if (!empty($values['user-settings'])) {
                    foreach ($values['user-settings'] as $settingId => $settingValue) {
                        $this->userSettings()->set($settingId, $settingValue, $id);
                    }
                }

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
                    $successMessages[] = 'Password successfully changed'; // @translate
                }

                $keyPersisted = false;
                if (!empty($values['edit-keys']['new-key-label']) || !empty($postData['delete'])) {
                    if (!$this->userIsAllowed($userEntity, 'edit-keys')) {
                        throw new Exception\PermissionDeniedException(
                            'User does not have permission to edit API keys'
                        );
                    }

                    // Create a new API key.
                    if (!empty($values['edit-keys']['new-key-label'])) {
                        $key = new ApiKey;
                        $key->setId();
                        $key->setLabel($values['edit-keys']['new-key-label']);
                        $key->setOwner($userEntity);
                        $keyId = $key->getId();
                        $keyCredential = $key->setCredential();
                        $this->entityManager->persist($key);
                        $keyPersisted = true;
                    }

                    // Remove any keys marked for deletion
                    if (!empty($postData['delete']) && is_array($postData['delete'])) {
                        foreach ($postData['delete'] as $deleteId) {
                            $keys->remove($deleteId);
                        }
                        $successMessages[] = 'Key(s) successfully deleted'; // @translate
                    }
                }

                $this->entityManager->flush();

                if ($keyPersisted) {
                    $message = new Message(
                        'API key successfully created.<br><br>Here is your key ID and credential for access to the API. WARNING: "key_credential" will be unretrievable after you navigate away from this page.<br><br>key_identity: <code>%s</code><br>key_credential: <code>%s</code>', // @translate
                        $keyId, $keyCredential
                    );
                    $message->setEscapeHtml(false);
                    $this->messenger()->addWarning($message);
                }

                foreach ($successMessages as $message) {
                    $this->messenger()->addSuccess($message);
                }
                return $this->redirect()->refresh();
            } else {
                $this->messenger()->addFormErrors($form);
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
                if ($response) {
                    $this->messenger()->addSuccess('User successfully deleted'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute(
            'admin/default',
            ['action' => 'browse'],
            true
        );
    }
}

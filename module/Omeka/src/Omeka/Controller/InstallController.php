<?php
namespace Omeka\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class InstallController extends AbstractActionController
{
    public function indexAction()
    {
        $installationMessages = array();
        $userCreationErrors = array();
        $user = array();

        if ($this->getRequest()->isPost()) {

            // Allow all privileges during installation.
            $acl = $this->getServiceLocator()->get('Acl');
            $acl->allow();

            // Perform the installation.
            $installationManager = $this->getServiceLocator()->get('InstallationManager');
            $result = $installationManager->install();
            $installationMessages = $result->getMessages();

            if (!$result->isError()) {
                // Create the global admin user.
                $api = $this->getServiceLocator()->get('ApiManager');
                $data = $this->getRequest()->getPost()->toArray();
                $data['role'] = 'global-admin';
                $response = $api->create('users', $data);
                if ($response->isError()) {
                    $userCreationErrors = $response->getErrors();
                } else {
                    // Set the password.
                    $user = $response->getContent();
                    $em = $this->getServiceLocator()->get('EntityManager');
                    $userEntity = $em->find('Omeka\Model\Entity\User', $user['id']);
                    $userEntity->setPassword($data['password']);
                    $em->flush();
                }
            }
        }

        return new ViewModel(array(
            'installation_messages' => $installationMessages,
            'user_creation_errors' => $userCreationErrors,
            'user' => $user,
        ));
    }
}

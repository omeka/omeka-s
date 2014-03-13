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
            // Perform the installation.
            $data = $this->getRequest()->getPost()->toArray();
            $installationManager = $this->getServiceLocator()->get('Omeka\InstallationManager');
            $installationManager->registerVars(
                'Omeka\Installation\Task\CreateFirstUserTask',
                array(
                    'username' => $data['username'],
                    'password' => $data['password'],
                    'name'     => $data['name'],
                    'email'    => $data['email']
                )
            );
            $result = $installationManager->install();
            $installationMessages = $result->getMessages();
        }

        return new ViewModel(array(
            'installation_messages' => $installationMessages,
            'user_creation_errors' => $userCreationErrors,
            'user' => $user,
        ));
    }
}

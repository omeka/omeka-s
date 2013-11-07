<?php
namespace Omeka\Controller\Install;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class InstallController extends AbstractActionController
{
    public function indexAction()
    {
        $messages = array();
        $isError = false;
        if ($this->getRequest()->isPost()) {
            $installationManager = $this->getServiceLocator()->get('InstallationManager');
            $result = $installationManager->install();
            $messages = $result->getMessages();
            $isError = $result->isError();
        }
        return new ViewModel(array(
            'messages' => $messages,
            'is_error' => $isError,
        ));
    }
}

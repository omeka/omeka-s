<?php
namespace Omeka\Controller;

use Omeka\Form\InstallationForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class InstallController extends AbstractActionController
{
    public function indexAction()
    {
        if ($this->getServiceLocator()->get('Omeka\Status')->isInstalled()) {
            return $this->redirect()->toRoute('admin');
        }

        $form = new InstallationForm($this->getServiceLocator());

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $manager = $this->getServiceLocator()->get('Omeka\Installer');
                $manager->registerVars(
                    'Omeka\Installation\Task\CreateFirstUserTask',
                    [
                        'password' => $data['password'],
                        'name'     => $data['name'],
                        'email'    => $data['email']
                    ]
                );
                $manager->registerVars(
                    'Omeka\Installation\Task\AddDefaultSettingsTask',
                    [
                        'administrator_email' => $data['email'],
                        'installation_title' => $data['installation_title'],
                    ]
                );
                if ($manager->install()) {
                    // Success. Redirect to login.
                    $this->messenger()->addSuccess('Installation successful. Please log in.');
                    return $this->redirect()->toRoute('login');
                } else {
                    // Error during installation.
                    $this->messenger()->addError('There were errors during installation.');
                    foreach ($manager->getErrors() as $error) {
                        $this->messenger()->addError($error);
                    }
                }
            } else {
                $this->messenger()->addError('There was an error during validation.');
            }
        }

        $this->layout('layout/minimal');
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;
    }
}

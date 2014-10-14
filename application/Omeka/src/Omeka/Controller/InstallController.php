<?php
namespace Omeka\Controller;

use Omeka\Form\InstallationForm;
use Omeka\Installation\Manager as InstallationManager;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class InstallController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $form = new InstallationForm;

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $manager = new InstallationManager($this->getServiceLocator());
                $manager->registerVars(
                    'Omeka\Installation\Task\CreateFirstUserTask',
                    array(
                        'username' => $data['username'],
                        'password' => $data['password'],
                        'name'     => $data['name'],
                        'email'    => $data['email']
                    )
                );
                if ($manager->install()) {
                    // Success. Redirect to login.
                    $this->messenger()->addSuccess('Installation successful.');
                    return $this->redirect()->toRoute('login');
                } else {
                    // Error during installation. Log installation messages.
                    $this->messenger()->addError('There was an error during installation.');
                    $this->getServiceLocator()->get('Omeka\Logger')->err($result->getMessages());
                }
            } else {
                $this->messenger()->addError('There was an error during validation.');
            }
        }

        $view->setVariable('form', $form);
        return $view;
    }
}

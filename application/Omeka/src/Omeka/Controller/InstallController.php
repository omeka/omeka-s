<?php
namespace Omeka\Controller;

use Omeka\Form\InstallationForm;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class InstallController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $form = new InstallationForm;

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                // Form is valid. Begin installation.
                $validatedData = $form->getData();
                $result = $this->install($validatedData);
                if ($result->isError()) {
                    // Error during installation. Log installation messages.
                    $this->messenger()->addError('There was an error during installation.');
                    $this->getServiceLocator()->get('Omeka\Logger')->err($result->getMessages());
                } else {
                    // Success. Redirect to login.
                    $this->messenger()->addSuccess('Installation successful.');
                    return $this->redirect()->toRoute('login');
                }
            } else {
                // Form is invalid.
                $this->messenger()->addError('There was an error during validation.');
            }
        }
        
        $view->setVariable('form', $form);
        return $view;
    }

    /**
     * Install Omeka.
     *
     * @param array $data
     * @return \Omeka\Installation\Result
     */
    protected function install(array $data)
    {
        $manager = $this->getServiceLocator()->get('Omeka\InstallationManager');
        $manager->registerVars(
            'Omeka\Installation\Task\CreateFirstUserTask',
            array(
                'username' => $data['username'],
                'password' => $data['password'],
                'name'     => $data['name'],
                'email'    => $data['email']
            )
        );
        return $manager->install();
    }
}

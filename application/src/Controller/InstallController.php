<?php
namespace Omeka\Controller;

use Omeka\Form\InstallationForm;
use Omeka\Installation\Installer;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class InstallController extends AbstractActionController
{
    /**
     * @var Installer
     */
    protected $installer;

    /**
     * @param Installer $installer
     */
    public function __construct(Installer $installer)
    {
        $this->installer = $installer;
    }

    public function indexAction()
    {
        if ($this->status()->isInstalled()) {
            return $this->redirect()->toRoute('admin');
        }

        $form = $this->getForm(InstallationForm::class);
        $view = new ViewModel;

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $this->installer->registerVars(
                    'Omeka\Installation\Task\CreateFirstUserTask',
                    $data['user']
                );
                $this->installer->registerVars(
                    'Omeka\Installation\Task\AddDefaultSettingsTask',
                    [
                        'administrator_email' => $data['user']['email'],
                        'installation_title' => $data['settings']['installation_title'],
                        'time_zone' => $data['settings']['time_zone'],
                        'locale' => $data['settings']['locale'],
                    ]
                );
                if ($this->installer->install()) {
                    // Success. Redirect to login.
                    $this->messenger()->addSuccess('Installation successful. Please log in.'); // @translate
                    return $this->redirect()->toRoute('login');
                } else {
                    // Error during installation.
                    $this->messenger()->addError('There were errors during installation.'); // @translate
                    foreach ($this->installer->getErrors() as $error) {
                        $this->messenger()->addError($error);
                    }
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        } else {
            if (!$this->installer->preInstall()) {
                $view->setVariable('preErrors', $this->installer->getErrors());
            }
        }

        $view->setVariable('form', $form);
        return $view;
    }
}

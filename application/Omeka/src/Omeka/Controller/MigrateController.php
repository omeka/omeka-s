<?php
namespace Omeka\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class MigrateController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $status = $this->getServiceLocator()->get('Omeka\Status');
        if (!$status->needsMigration()) {
            return $this->redirect()->toRoute('admin');
        }
        if ($this->getRequest()->isPost()) {
            // Perform migrations and update the installed version.
            $migrator = $this->getServiceLocator()
                ->get('Omeka\MigrationManager')
                ->upgrade();
            $options = $this->getServiceLocator()
                ->get('Omeka\Options')
                ->set('version', $codeVersion);
            $this->messenger()->addSuccess("Migration successful");
            return $this->redirect()->toRoute('admin');
        }
        return $view;
    }
}

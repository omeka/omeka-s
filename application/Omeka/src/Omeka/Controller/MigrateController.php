<?php
namespace Omeka\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class MigrateController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $migrator = $this->getServiceLocator()->get('Omeka\MigrationManager');
        if (!$migrator->getMigrationsToPerform()) {
            return $this->redirect()->toRoute('admin');
        }
        if ($this->getRequest()->isPost()) {
            $migrator->upgrade();
            $this->messenger()->addSuccess("Migration successful");
            return $this->redirect()->toRoute('admin');
        }
        return $view;
    }
}

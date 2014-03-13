<?php
namespace Omeka\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class MigrateController extends AbstractActionController
{
    public function indexAction()
    {
        $migrator = $this->getServiceLocator()->get('MigrationManager');

        if ($this->getRequest()->isPost()) {
            $migrator->upgrade();
        }

        return new ViewModel(array(
            'pending' => $migrator->getMigrationsToPerform(),
        ));
    }
}

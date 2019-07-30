<?php
namespace Omeka\Controller;

use Omeka\Db\Migration\Manager;
use Omeka\Stdlib\Environment;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class MigrateController extends AbstractActionController
{
    /**
     * @var Manager
     */
    protected $migrationManager;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @param Manager $migrationManager
     */
    public function __construct(Manager $migrationManager, Environment $environment)
    {
        $this->migrationManager = $migrationManager;
        $this->environment = $environment;
    }

    public function indexAction()
    {
        if (!$this->status()->needsMigration()) {
            return $this->redirect()->toRoute('admin');
        }

        if ($this->getRequest()->isPost() && $this->environment->isCompatible()) {
            // Perform migrations and update the installed version.
            $this->migrationManager->upgrade();
            $this->settings()->set('version', $this->status()->getVersion());
            $this->messenger()->addSuccess("Migration successful"); // @translate
            return $this->redirect()->toRoute('admin');
        }

        $view = new ViewModel;
        $view->setVariable('environment', $this->environment);
        return $view;
    }
}

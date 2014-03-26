<?php
namespace Omeka\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ModuleController extends AbstractActionController
{
    public function indexAction()
    {
        $view = new ViewModel;
        $modules = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $view->setVariable('modules', $modules->getModules());

        if ($this->getRequest()->isPost()) {
            $id = $this->params()->fromPost('id');
            $action = $this->params()->fromPost('action');
            switch ($action) {
                case 'install':
                    $modules->install($id);
                    // @todo This is where we should redirect to module
                    // configuration page (if any)
                    break;
                case 'uninstall':
                    $modules->uninstall($id);
                    break;
                case 'activate':
                    $modules->activate($id);
                    break;
                case 'deactivate':
                    $modules->deactivate($id);
                    break;
                case 'upgrade':
                    $modules->upgrade($id);
                    break;
                default:
                    break;
            }
            // Reload to update module states
            $this->redirect()->toRoute(
                'admin/default', array('controller' => 'module')
            );
        }

        return $view;
    }
}

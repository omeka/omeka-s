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

        if ($this->getRequest()->isPost()) {
            $id = $this->params()->fromPost('id');
            $action = $this->params()->fromPost('action');
            switch ($action) {
                case 'install':
                    $modules->install($id);
                    if ($modules->moduleIsConfigurable($id)) {
                        return $this->redirect()->toRoute(
                            'admin/default',
                            array('controller' => 'module', 'action' => 'configure'),
                            array('query' => array('id' => $id))
                        );
                    }
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
                case 'configure':
                    return $this->redirect()->toRoute(
                        'admin/default',
                        array('controller' => 'module', 'action' => 'configure'),
                        array('query' => array('id' => $id))
                    );
                default:
                    break;
            }
            return $this->redirect()->refresh();
        }

        $view->setVariable('modules', $modules);
        return $view;
    }

    public function configureAction()
    {
        $view = new ViewModel;

        // Get the module
        $id = $this->params()->fromQuery('id');
        $modules = $this->getServiceLocator()->get('ModuleManager');
        $module = $modules->getModule($id);
        if (null === $module) {
            return $this->redirect()->toRoute(
                'admin/default',
                array('controller' => 'module')
            );
        }

        if ($this->getRequest()->isPost()) {
            $module->handleConfigForm($this);
            return $this->redirect()->toRoute(
                'admin/default',
                array('controller' => 'module')
            );
        }

        $view->setVariable('config_form', $module->getConfigForm($view));
        return $view;
    }
}

<?php
namespace Omeka\Controller\Admin;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ModuleController extends AbstractActionController
{
    public function indexAction()
    {
        return $this->redirect()->toRoute('admin/default', array(
            'controller' => 'module',
            'action' => 'browse',
        ));
    }

    public function browseAction()
    {
        $view = new ViewModel;
        $modules = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $flashMessenger = $this->flashMessenger();

        if ($this->getRequest()->isPost()) {
            $id = $this->params()->fromPost('id');
            $action = $this->params()->fromPost('action');
            switch ($action) {
                case 'install':
                    $modules->install($id);
                    $flashMessenger->addSuccessMessage(
                        'The module was successfully installed'
                    );
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
                    $flashMessenger->addSuccessMessage(
                        'The module was successfully uninstalled'
                    );
                    break;
                case 'activate':
                    $modules->activate($id);
                    $flashMessenger->addSuccessMessage(
                        'The module was successfully activated'
                    );
                    break;
                case 'deactivate':
                    $modules->deactivate($id);
                    $flashMessenger->addSuccessMessage(
                        'The module was successfully deactivated'
                    );
                    break;
                case 'upgrade':
                    $modules->upgrade($id);
                    $flashMessenger->addSuccessMessage(
                        'The module was successfully upgraded'
                    );
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

        if ($flashMessenger->hasSuccessMessages()) {
            $view->setVariable(
                'successMessages',
                $flashMessenger->getSuccessMessages()
            );
        }
        $view->setVariable('modules', $modules);
        return $view;
    }

    public function configureAction()
    {
        $view = new ViewModel;
        $flashMessenger = $this->flashMessenger();

        // Get the module
        $id = $this->params()->fromQuery('id');
        $modules = $this->getServiceLocator()->get('ModuleManager');
        $module = $modules->getModule($id);
        if (null === $module) {
            return $this->redirect()->toRoute('admin/default', array(
                'controller' => 'module',
                'action' => 'browse',
            ));
        }

        if ($this->getRequest()->isPost()) {
            $module->handleConfigForm($this);
            $flashMessenger->addSuccessMessage(
                'The module was successfully configured'
            );
            return $this->redirect()->toRoute('admin/default', array(
                'controller' => 'module',
                'action' => 'browse',
            ));
        }

        if ($flashMessenger->hasSuccessMessages()) {
            $view->setVariable(
                'successMessages',
                $flashMessenger->getSuccessMessages()
            );
        }
        $view->setVariable('configForm', $module->getConfigForm($view));
        return $view;
    }
}

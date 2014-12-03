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
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');

        // Handle state changes.
        $action = $this->params()->fromQuery('action');
        $id = $this->params()->fromQuery('id');
        if ($action && $module = $manager->getModule($id)) {
            if ('install' == $action) {
                $manager->install($module);
                $this->messenger()->addSuccess('The module was successfully installed');
                if ($module->isConfigurable()) {
                    return $this->redirect()->toRoute(
                        'admin/default',
                        array('controller' => 'module', 'action' => 'configure'),
                        array('query' => array('id' => $module->getId()))
                    );
                }
            } elseif ('uninstall' == $action) {
                $manager->uninstall($module);
                $this->messenger()->addSuccess('The module was successfully uninstalled');
            } elseif ('configure' == $action) {
                return $this->redirect()->toRoute(
                    'admin/default',
                    array('controller' => 'module', 'action' => 'configure'),
                    array('query' => array('id' => $module->getId()))
                );
            } elseif ('activate' == $action) {
                $manager->activate($module);
                $this->messenger()->addSuccess('The module was successfully activated');
            } elseif ('deactivate' == $action) {
                $manager->deactivate($module);
                $this->messenger()->addSuccess('The module was successfully deactivated');
            } elseif ('upgrade' == $action) {
                $manager->upgrade($module);
                $this->messenger()->addSuccess('The module was successfully upgraded');
            }
            return $this->redirect()->refresh();
        }

        // Get modules, filtering modules by state.
        $state = $this->params()->fromQuery('state');
        if ($state) {
            $modules = $manager->getModulesByState($state);
        } else {
            $modules = $manager->getModules();
        }

        // Order modules by name.
        uasort($modules, function($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });

        $view->setVariable('modules', $modules);
        $view->setVariable('state', $state);
        $view->setVariable('states', array(
            'active'         => 'Active',
            'not_active'     => 'Not Active',
            'not_installed'  => 'Not Installed',
            'needs_upgrade'  => 'Needs Upgrade',
            'not_found'      => 'Not Found',
            'invalid_module' => 'Invalid Module',
            'invalid_ini'    => 'Invalid Ini',
        ));
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

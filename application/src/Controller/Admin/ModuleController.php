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

        // Handle bulk state changes (activate and deactivate only)
        if ($this->getRequest()->isPost()) {
            if ($ids = $this->params()->fromPost('activate')) {
                foreach ($ids as $id) {
                    $module = $manager->getModule($id);
                    $manager->activate($module);
                }
                $this->messenger()->addSuccess($this->translate('The selected modules were successfully activated'));
            } elseif ($ids = $this->params()->fromPost('deactivate')) {
                foreach ($ids as $id) {
                    $module = $manager->getModule($id);
                    $manager->deactivate($module);
                }
                $this->messenger()->addSuccess($this->translate('The selected modules were successfully deactivated'));
            }
            return $this->redirect()->refresh();
        }

        // Handle individual state changes.
        $action = $this->params()->fromQuery('action');
        $id = $this->params()->fromQuery('id');
        if ($action && $module = $manager->getModule($id)) {
            if ('install' == $action) {
                $manager->install($module);
                $this->messenger()->addSuccess($this->translate('The module was successfully installed'));
                if ($module->isConfigurable()) {
                    return $this->redirect()->toRoute(
                        'admin/default',
                        array('controller' => 'module', 'action' => 'configure'),
                        array('query' => array('id' => $module->getId()))
                    );
                }
            } elseif ('uninstall' == $action) {
                $manager->uninstall($module);
                $this->messenger()->addSuccess($this->translate('The module was successfully uninstalled'));
            } elseif ('configure' == $action) {
                return $this->redirect()->toRoute(
                    'admin/default',
                    array('controller' => 'module', 'action' => 'configure'),
                    array('query' => array('id' => $module->getId()))
                );
            } elseif ('activate' == $action) {
                $manager->activate($module);
                $this->messenger()->addSuccess($this->translate('The module was successfully activated'));
            } elseif ('deactivate' == $action) {
                $manager->deactivate($module);
                $this->messenger()->addSuccess($this->translate('The module was successfully deactivated'));
            } elseif ('upgrade' == $action) {
                $manager->upgrade($module);
                $this->messenger()->addSuccess($this->translate('The module was successfully upgraded'));
            }
            return $this->redirect()->refresh();
        }

        // Get modules, filtering modules by state.
        $state = $this->params()->fromQuery('state');
        if ('error' == $state) {
            $modules = array_merge(
                $manager->getModulesByState('not_found'),
                $manager->getModulesByState('invalid_module'),
                $manager->getModulesByState('invalid_ini')
            );
        } elseif ($state) {
            $modules = $manager->getModulesByState($state);
        } else {
            $modules = $manager->getModules();
        }

        // Order modules by name.
        uasort($modules, function($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });

        $view->setVariable('modules', $modules);
        $view->setVariable('filterState', $state);
        $view->setVariable('filterStates', array(
            'active'        => $this->translate('Active'),
            'not_active'    => $this->translate('Not Active'),
            'not_installed' => $this->translate('Not Installed'),
            'needs_upgrade' => $this->translate('Needs Upgrade'),
            'error'         => $this->translate('Error'),
        ));
        $view->setVariable('states', array(
            'active'         => $this->translate('Active'),
            'not_active'     => $this->translate('Not Active'),
            'not_installed'  => $this->translate('Not Installed'),
            'needs_upgrade'  => $this->translate('Needs Upgrade'),
            'not_found'      => $this->translate('Not Found'),
            'invalid_module' => $this->translate('Invalid Module'),
            'invalid_ini'    => $this->translate('Invalid Ini'),
        ));
        return $view;
    }

    public function configureAction()
    {
        $view = new ViewModel;

        // Get the module
        $id = $this->params()->fromQuery('id');
        $module = $this->getServiceLocator()
            ->get('ModuleManager')->getModule($id);

        if (null === $module) {
            // Do not attempt to configure an unloaded module.
            return $this->redirect()->toRoute('admin/default', array(
                'controller' => 'module',
                'action' => 'browse',
            ));
        }

        if ($this->getRequest()->isPost()) {
            if (false !== $module->handleConfigForm($this)) {
                $this->messenger()->addSuccess($this->translate('The module was successfully configured'));
                return $this->redirect()->toRoute('admin/default', array(
                    'controller' => 'module',
                    'action' => 'browse',
                ));
            }
            $this->messenger()->addError($this->translate('There was a problem during configuration'));
        }

        $view->setVariable('module', $this->getServiceLocator()
            ->get('Omeka\ModuleManager')->getModule($id));
        $view->setVariable('configForm', $module->getConfigForm($view));
        return $view;
    }
}

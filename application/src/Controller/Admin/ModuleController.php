<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ModuleStateChangeForm;
use Omeka\Form\ModuleUninstallForm;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Mvc\Exception;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ModuleController extends AbstractActionController
{
    public function browseAction()
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');

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
        uasort($modules, function ($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });

        $view = new ViewModel;
        $view->setVariable('modules', $modules);
        $view->setVariable('filterState', $state);
        $view->setVariable('filterStates', [
            'active'        => $this->translate('Active'),
            'not_active'    => $this->translate('Not Active'),
            'not_installed' => $this->translate('Not Installed'),
            'needs_upgrade' => $this->translate('Needs Upgrade'),
            'error'         => $this->translate('Error'),
        ]);
        $view->setVariable('states', [
            'active'         => $this->translate('Active'),
            'not_active'     => $this->translate('Not Active'),
            'not_installed'  => $this->translate('Not Installed'),
            'needs_upgrade'  => $this->translate('Needs Upgrade'),
            'not_found'      => $this->translate('Not Found'),
            'invalid_module' => $this->translate('Invalid Module'),
            'invalid_ini'    => $this->translate('Invalid Ini'),
        ]);
        $view->setVariable('stateChangeForm', function ($action, $id) {
            return new ModuleStateChangeForm($this->getServiceLocator(), $action,
                ['module_action' => $action, 'module_id' => $id]
            );
        });
        $view->setVariable('uninstallForm', new ModuleUninstallForm(
            $this->getServiceLocator(), 'uninstall'
        ));
        return $view;
    }

    /**
     * Install a module.
     */
    public function installAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }
        $id = $this->params()->fromQuery('id');
        $form = new ModuleStateChangeForm($this->getServiceLocator(), 'install',
            ['module_action' => 'install', 'module_id' => $id]
        );
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $module = $manager->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        try {
            $manager->install($module);
        } catch (ModuleCannotInstallException $e) {
            $this->messenger()->addError($e->getMessage());
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }
        $this->messenger()->addSuccess($this->translate('The module was successfully installed'));
        if ($module->isConfigurable()) {
            return $this->redirect()->toRoute(
                null, ['action' => 'configure'],
                ['query' => ['id' => $module->getId()]], true
            );
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    /**
     * Uninstall a module.
     */
    public function uninstallAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }
        $id = $this->params()->fromQuery('id');
        $form = new ModuleUninstallForm($this->getServiceLocator(), 'uninstall');
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $module = $manager->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        $manager->uninstall($module);
        $this->messenger()->addSuccess($this->translate('The module was successfully uninstalled'));
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    /**
     * Activate a module or modules.
     */
    public function activateAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }
        $id = $this->params()->fromQuery('id');
        $form = new ModuleStateChangeForm($this->getServiceLocator(), 'activate',
            ['module_action' => 'activate', 'module_id' => $id]
        );
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $module = $manager->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        $manager->activate($module);
        $this->messenger()->addSuccess($this->translate('The module was successfully activated'));
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    /**
     * Deactivate a module or modules.
     */
    public function deactivateAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }
        $id = $this->params()->fromQuery('id');
        $form = new ModuleStateChangeForm($this->getServiceLocator(), 'deactivate',
            ['module_action' => 'deactivate', 'module_id' => $id]
        );
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $module = $manager->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        $manager->deactivate($module);
        $this->messenger()->addSuccess($this->translate('The module was successfully deactivated'));
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    /**
     * Upgrade a module.
     */
    public function upgradeAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }
        $id = $this->params()->fromQuery('id');
        $form = new ModuleStateChangeForm($this->getServiceLocator(), 'upgrade',
            ['module_action' => 'upgrade', 'module_id' => $id]
        );
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $module = $manager->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        $manager->upgrade($module);
        $this->messenger()->addSuccess($this->translate('The module was successfully upgraded'));
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    /**
     * Configure a module.
     */
    public function configureAction()
    {
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $id = $this->params()->fromQuery('id');
        $module = $manager->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }

        $moduleObject = $this->getServiceLocator()
            ->get('ModuleManager')->getModule($id);
        if (null === $moduleObject) {
            // Do not attempt to configure an unloaded module.
            throw new Exception\NotFoundException;
        }

        if ($this->getRequest()->isPost()) {
            if (false !== $moduleObject->handleConfigForm($this)) {
                $this->messenger()->addSuccess($this->translate('The module was successfully configured'));
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            }
            $this->messenger()->addError($this->translate('There was a problem during configuration'));
        }

        $view = new ViewModel;
        $renderer = $this->getServiceLocator()->get('ViewRenderer');
        $view->setVariable('configForm', $moduleObject->getConfigForm($renderer));
        $view->setVariable('module', $module);
        return $view;
    }

    public function showDetailsAction()
    {
        $id = $this->params()->fromQuery('id');
        $manager = $this->getServiceLocator()->get('Omeka\ModuleManager');
        $module = $manager->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('module', $module);
        return $view;
    }
}

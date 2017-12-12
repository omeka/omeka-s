<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ModuleStateChangeForm;
use Omeka\Form\ConfirmForm;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Module\Manager as OmekaModuleManager;
use Zend\ModuleManager\ModuleManager;
use Omeka\Mvc\Exception;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;

class ModuleController extends AbstractActionController
{
    /**
     * @var PhpRenderer
     */
    protected $viewRenderer;

    /**
     * @var ModuleManager
     */
    protected $modules;

    /**
     * @var OmekaModuleManager
     */
    protected $omekaModules;

    /**
     * @param PhpRenderer $viewRenderer
     * @param ModuleManager $modules
     * @param OmekaModuleManager $omekaModules
     */
    public function __construct(PhpRenderer $viewRenderer, ModuleManager $modules,
        OmekaModuleManager $omekaModules
    ) {
        $this->viewRenderer = $viewRenderer;
        $this->modules = $modules;
        $this->omekaModules = $omekaModules;
    }

    public function browseAction()
    {
        // Get modules, filtering modules by state.
        $state = $this->params()->fromQuery('state');
        if ('error' == $state) {
            $modules = array_merge(
                $this->omekaModules->getModulesByState('not_found'),
                $this->omekaModules->getModulesByState('invalid_module'),
                $this->omekaModules->getModulesByState('invalid_ini')
            );
        } elseif ($state) {
            $modules = $this->omekaModules->getModulesByState($state);
        } else {
            $modules = $this->omekaModules->getModules();
        }

        // Order modules by name.
        uasort($modules, function ($a, $b) {
            return strcmp(strtolower($a->getName()), strtolower($b->getName()));
        });

        $view = new ViewModel;
        $view->setVariable('modules', $modules);
        $view->setVariable('filterState', $state);
        $view->setVariable('filterStates', [
            'active' => $this->translate('Active'),
            'not_active' => $this->translate('Not active'),
            'not_installed' => $this->translate('Not installed'),
            'needs_upgrade' => $this->translate('Needs upgrade'),
            'error' => $this->translate('Error'),
        ]);
        $view->setVariable('states', [
            'active' => $this->translate('Active'),
            'not_active' => $this->translate('Not active'),
            'not_installed' => $this->translate('Not installed'),
            'needs_upgrade' => $this->translate('Needs upgrade'),
            'not_found' => $this->translate('Not found'),
            'invalid_module' => $this->translate('Invalid module'),
            'invalid_ini' => $this->translate('Invalid INI'),
            'invalid_omeka_version' => $this->translate('Invalid Omeka S version'),
        ]);
        $view->setVariable('stateChangeForm', function ($action, $id) {
            return $this->getForm(ModuleStateChangeForm::class, [
                'module_action' => $action,
                'module_id' => $id,
            ]);
        });
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
        $form = $this->getForm(ModuleStateChangeForm::class, [
            'module_action' => 'install',
            'module_id' => $id,
        ]);
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $module = $this->omekaModules->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        try {
            $this->omekaModules->install($module);
        } catch (ModuleCannotInstallException $e) {
            $this->messenger()->addError($e->getMessage());
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }
        $this->messenger()->addSuccess('The module was successfully installed'); // @translate
        if ($module->isConfigurable()) {
            return $this->redirect()->toRoute(
                null, ['action' => 'configure'],
                ['query' => ['id' => $module->getId()]], true
            );
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function uninstallConfirmAction()
    {
        $id = $this->params()->fromQuery('id');
        $module = $this->omekaModules->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }

        $form = $this->getForm(ConfirmForm::class);
        $form->setAttribute('action', $this->url()->fromRoute(
            null, ['action' => 'uninstall'], ['query' => ['id' => $module->getId()],
        ], true));
        $form->setButtonLabel('Confirm uninstall'); // @translate

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('omeka/admin/module/uninstall-confirm');
        $view->setVariable('form', $form);
        $view->setVariable('module', $module);
        return $view;
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
        $form = $this->getForm(ConfirmForm::class);
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $module = $this->omekaModules->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        $this->omekaModules->uninstall($module);
        $this->messenger()->addSuccess('The module was successfully uninstalled'); // @translate
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
        $form = $this->getForm(ModuleStateChangeForm::class, [
            'module_action' => 'activate',
            'module_id' => $id,
        ]);
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $module = $this->omekaModules->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        $this->omekaModules->activate($module);
        $this->messenger()->addSuccess('The module was successfully activated'); // @translate
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
        $form = $this->getForm(ModuleStateChangeForm::class, [
            'module_action' => 'deactivate',
            'module_id' => $id,
        ]);
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $module = $this->omekaModules->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        $this->omekaModules->deactivate($module);
        $this->messenger()->addSuccess('The module was successfully deactivated'); // @translate
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
        $form = $this->getForm(ModuleStateChangeForm::class, [
            'module_action' => 'upgrade',
            'module_id' => $id,
        ]);
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }
        $module = $this->omekaModules->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }
        $this->omekaModules->upgrade($module);
        $this->messenger()->addSuccess('The module was successfully upgraded'); // @translate
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    /**
     * Configure a module.
     */
    public function configureAction()
    {
        $id = $this->params()->fromQuery('id');
        $module = $this->omekaModules->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }

        $moduleObject = $this->modules->getModule($id);
        if (null === $moduleObject) {
            // Do not attempt to configure an unloaded module.
            throw new Exception\NotFoundException;
        }

        if ($this->getRequest()->isPost()) {
            if (false !== $moduleObject->handleConfigForm($this)) {
                $this->messenger()->addSuccess('The module was successfully configured'); // @translate
                return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
            }
            $this->messenger()->addError('There was a problem during configuration'); // @translate
        }

        $view = new ViewModel;
        $view->setVariable('configForm', $moduleObject->getConfigForm($this->viewRenderer));
        $view->setVariable('module', $module);
        return $view;
    }

    public function showDetailsAction()
    {
        $id = $this->params()->fromQuery('id');
        $module = $this->omekaModules->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('module', $module);
        return $view;
    }
}

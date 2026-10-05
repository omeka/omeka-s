<?php
namespace Omeka\Controller\Admin;

use Omeka\Form\ModuleStateChangeForm;
use Omeka\Form\ConfirmForm;
use Omeka\Module\Exception\ModuleCannotInstallException;
use Omeka\Module\Exception\ModuleStateInvalidException;
use Omeka\Module\Manager as OmekaModuleManager;
use Laminas\ModuleManager\ModuleManager;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\Message;
use Laminas\Form\Form;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\PhpRenderer;

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
        // Get all modules. The page filters them by state in the browser, and
        // the state query parameter only selects the filter to start with.
        $modules = $this->omekaModules->getModules();

        // Order modules by name.
        uasort($modules, function ($a, $b) {
            return strcmp(strtolower($a->getName()), strtolower($b->getName()));
        });

        $view = new ViewModel;
        $view->setVariable('modules', $modules);
        $view->setVariable('filterState', $this->params()->fromQuery('state'));
        $view->setVariable('filterStates', [
            'active' => $this->translate('Active'),
            'not_active' => $this->translate('Not active'),
            'not_installed' => $this->translate('Not installed'),
            'needs_upgrade' => $this->translate('Needs upgrade'),
            'error' => $this->translate('Error'),
        ]);
        $view->setVariable('states', $this->getStateLabels());
        $view->setVariable('stateChangeForm', function ($action, $id) {
            return $this->getForm(ModuleStateChangeForm::class, [
                'module_action' => $action,
                'module_id' => $id,
            ]);
        });
        $view->setVariable('batchForm', $this->getForm(Form::class, ['name' => 'module_batch']));
        return $view;
    }

    /**
     * Activate or deactivate the selected modules.
     *
     * Modules not in the state the action needs are skipped. A failure is
     * reported and the remaining modules are still processed.
     */
    public function batchAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }
        $form = $this->getForm(Form::class, ['name' => 'module_batch']);
        $form->setData($this->getRequest()->getPost());
        if (!$form->isValid()) {
            throw new Exception\PermissionDeniedException;
        }

        // The manager method each action calls, and the message listing the
        // modules it changed.
        $actions = [
            'activate-selected' => [
                'activate',
                'Modules activated: %s', // @translate
            ],
            'deactivate-selected' => [
                'deactivate',
                'Modules deactivated: %s', // @translate
            ],
        ];
        $action = $this->params()->fromPost('batch_action');
        if (!isset($actions[$action])) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }
        [$method, $successMessage] = $actions[$action];

        // Module IDs are strings. Anything else, such as a nested array in a
        // crafted request, is ignored.
        $ids = array_unique(array_filter((array) $this->params()->fromPost('module_ids', []), 'is_string'));
        $changed = [];
        $skipped = [];
        foreach ($ids as $id) {
            $module = $this->omekaModules->getModule($id);
            $label = ($module ? $module->getName() : null) ?: $id;
            if (!$module) {
                $skipped[] = $label;
                continue;
            }
            try {
                $this->omekaModules->$method($module);
                $changed[] = $label;
            } catch (ModuleStateInvalidException $e) {
                // The manager only changes a module in the state the action
                // needs, and throws this before changing anything.
                $skipped[] = $label;
            } catch (\Exception $e) {
                $this->messenger()->addError(new Message('%1$s: %2$s', $label, $e->getMessage()));
            }
        }
        if ($changed) {
            $this->messenger()->addSuccess(new Message($successMessage, implode(', ', $changed)));
        }
        if ($skipped) {
            $this->messenger()->addWarning(new Message(
                'Modules skipped because the action does not apply to their current state: %s', // @translate
                implode(', ', $skipped)
            ));
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
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
        } catch (ModuleCannotInstallException | ModuleStateInvalidException $e) {
            // A state error means the page was out of date, such as after Back
            // or a second click. It's thrown before anything changes.
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
        $form->setAttribute(
            'action',
            $this->url()->fromRoute(
                null,
                ['action' => 'uninstall'],
                ['query' => ['id' => $module->getId()]],
                true
            )
        );
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
        try {
            $this->omekaModules->uninstall($module);
            $this->messenger()->addSuccess('The module was successfully uninstalled'); // @translate
        } catch (ModuleStateInvalidException $e) {
            // The page was out of date. This is thrown before anything changes.
            $this->messenger()->addError($e->getMessage());
        }
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
        try {
            $this->omekaModules->activate($module);
            $this->messenger()->addSuccess('The module was successfully activated'); // @translate
        } catch (ModuleStateInvalidException $e) {
            // The page was out of date. This is thrown before anything changes.
            $this->messenger()->addError($e->getMessage());
        }
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
        try {
            $this->omekaModules->deactivate($module);
            $this->messenger()->addSuccess('The module was successfully deactivated'); // @translate
        } catch (ModuleStateInvalidException $e) {
            // The page was out of date. This is thrown before anything changes.
            $this->messenger()->addError($e->getMessage());
        }
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
        try {
            $this->omekaModules->upgrade($module);
            $this->messenger()->addSuccess('The module was successfully upgraded'); // @translate
        } catch (ModuleStateInvalidException $e) {
            // The page was out of date. This is thrown before anything changes.
            $this->messenger()->addError($e->getMessage());
        }
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

        $formName = "module_{$id}_configure";
        $csrfForm = $this->getForm(Form::class, ['name' => $formName]);

        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $csrfForm->setData($data);
            if ($csrfForm->isValid()) {
                unset($this->getRequest()->getPost()["{$formName}_csrf"]);
                if (false !== $moduleObject->handleConfigForm($this)) {
                    $this->messenger()->addSuccess('The module was successfully configured'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
                }
                $this->messenger()->addError('There was a problem during configuration'); // @translate
            } else {
                $this->messenger()->addFormErrors($csrfForm);
            }
        }

        $view = new ViewModel;
        $view->setVariable('configForm', $moduleObject->getConfigForm($this->viewRenderer));
        $view->setVariable('module', $module);
        $view->setVariable('csrfForm', $csrfForm);
        return $view;
    }

    /**
     * Show a module's details in the modules page sidebar.
     *
     * This has its own template. The show-details partial belongs to the
     * uninstall confirmation, where modules add warnings through view.details.
     */
    public function showDetailsAction()
    {
        $id = $this->params()->fromQuery('id');
        $module = $this->omekaModules->getModule($id);
        if (!$module) {
            throw new Exception\NotFoundException;
        }

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('omeka/admin/module/details');
        $view->setVariable('module', $module);
        $view->setVariable('states', $this->getStateLabels());
        return $view;
    }

    /**
     * Get the label for each module state.
     *
     * @return array
     */
    protected function getStateLabels()
    {
        return [
            'active' => $this->translate('Active'),
            'not_active' => $this->translate('Not active'),
            'not_installed' => $this->translate('Not installed'),
            'needs_upgrade' => $this->translate('Needs upgrade'),
            'not_found' => $this->translate('Not found'),
            'invalid_module' => $this->translate('Invalid module'),
            'invalid_ini' => $this->translate('Invalid INI'),
            'invalid_omeka_version' => $this->translate('Invalid Omeka S version'),
        ];
    }
}

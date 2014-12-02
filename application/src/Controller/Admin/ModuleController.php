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

        // Filter modules by state.
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

        $view->setVariable('manager', $manager);
        $view->setVariable('modules', $modules);
        $view->setVariable('state', $state);
        $view->setVariable('states', array(
            'active'         => 'Active',
            'not_active'     => 'Not Active',
            'not_installed'  => 'Not Installed',
            'not_found'      => 'Not Found',
            'invalid_module' => 'Invalid Module',
            'invalid_ini'    => 'Invalid Ini',
            'needs_upgrade'  => 'Needs Upgrade',
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

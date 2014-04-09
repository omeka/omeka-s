<?php
namespace Omeka\Mvc;

use Omeka\Module\Manager as ModuleManager;
use Zend\Mvc\Service;
use Zend\ServiceManager\ServiceManager;

/**
 * Application class for invoking the Omeka application.
 */
class Application
{
    const ERROR_CONTROLLER_PERMISSION_DENIED = 'error-controller-permission-denied';

    /**
     * {@inheritDoc}
     */
    public static function init($configuration = array())
    {
        $smConfig = isset($configuration['service_manager'])
            ? $configuration['service_manager'] : array();
        $serviceManager = new ServiceManager(new Service\ServiceManagerConfig($smConfig));
        $serviceManager->setService('ApplicationConfig', $configuration);

        $moduleManager = $serviceManager->get('ModuleManager');

        if ($serviceManager->get('Omeka\InstallationStatus')->isInstalled()) {
            // If Omeka is installed, merge application modules with active user
            // modules and set them all to be loaded.
            $activeModules = $serviceManager->get('Omeka\ModuleManager')
                ->getModulesByState(ModuleManager::STATE_ACTIVE);
            $moduleManager->setModules(array_merge(
                $configuration['modules'],
                array_keys($activeModules)
            ));
        }

        $moduleManager->loadModules();

        $listenersFromAppConfig = isset($configuration['listeners'])
            ? $configuration['listeners'] : array();
        $config = $serviceManager->get('Config');
        $listenersFromConfigService = isset($config['listeners'])
            ? $config['listeners'] : array();

        $listeners = array_unique(array_merge(
            $listenersFromConfigService,
            $listenersFromAppConfig
        ));
        return $serviceManager->get('Application')->bootstrap($listeners);

    }
}

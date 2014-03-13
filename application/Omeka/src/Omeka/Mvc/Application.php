<?php
namespace Omeka\Mvc;

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
        $smConfig = isset($configuration['service_manager']) ? $configuration['service_manager'] : array();
        $serviceManager = new ServiceManager(new Service\ServiceManagerConfig($smConfig));
        $serviceManager->setService('ApplicationConfig', $configuration);

        // Merge modules that are defined in configuration and those flagged
        // active in the database. Set all modules before loading them.
        $activeModules = $serviceManager->get('ActiveModules');
        $serviceManager
            ->get('ModuleManager')
            ->setModules(array_merge($configuration['modules'], $activeModules))
            ->loadModules();

        $listenersFromAppConfig     = isset($configuration['listeners']) ? $configuration['listeners'] : array();
        $config                     = $serviceManager->get('Config');
        $listenersFromConfigService = isset($config['listeners']) ? $config['listeners'] : array();

        $listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));

        return $serviceManager->get('Application')->bootstrap($listeners);

    }
}

<?php
namespace Omeka\Mvc;

use Omeka\Module\Manager as ModuleManager;
use Zend\Mvc\Application as ZendApplication;
use Zend\Mvc\Service;
use Zend\ServiceManager\ServiceManager;

/**
 * Application class for invoking the Omeka application.
 */
class Application
{
    const ERROR_ROUTER_PERMISSION_DENIED = 'error-router-permission-denied';

    /**
     * Initialize the Omeka S application.
     *
     * @see ZendApplication::init()
     * @return ZendApplication
     */
    public static function init($configuration = [])
    {
        $smConfig = isset($configuration['service_manager']) ? $configuration['service_manager'] : [];
        $serviceManager = new ServiceManager(new Service\ServiceManagerConfig($smConfig));
        $serviceManager->setService('ApplicationConfig', $configuration);
        $moduleManager = $serviceManager->get('ModuleManager');

        // Merge application modules with active user modules and load them.
        $activeModules = $serviceManager->get('Omeka\ModuleManager')->getModulesByState(ModuleManager::STATE_ACTIVE);
        $moduleManager->setModules(array_merge($configuration['modules'], array_keys($activeModules)));
        $moduleManager->loadModules();

        $listenersFromAppConfig = isset($configuration['listeners']) ? $configuration['listeners'] : [];
        $config = $serviceManager->get('Config');
        $listenersFromConfigService = isset($config['listeners']) ? $config['listeners'] : [];
        $listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));
        return $serviceManager->get('Application')->bootstrap($listeners);
    }
}

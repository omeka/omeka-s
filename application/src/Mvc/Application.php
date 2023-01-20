<?php
namespace Omeka\Mvc;

use Omeka\Module\Manager as ModuleManager;
use Laminas\Mvc\Application as ZendApplication;
use Laminas\Mvc\Service;
use Laminas\ServiceManager\ServiceManager;

/**
 * Application class for invoking the Omeka application.
 */
class Application
{
    /**
     * Initialize the Omeka S application.
     *
     * @see ZendApplication::init()
     * @return ZendApplication
     */
    public static function init($configuration = [])
    {
        $smConfig = $configuration['service_manager'] ?? [];
        $smConfig = new Service\ServiceManagerConfig($smConfig);

        $serviceManager = new ServiceManager;
        $smConfig->configureServiceManager($serviceManager);
        $serviceManager->setService('ApplicationConfig', $configuration);

        $moduleManager = $serviceManager->get('ModuleManager');

        // Merge application modules with active user modules and load them.
        $activeModules = $serviceManager->get('Omeka\ModuleManager')->getModulesByState(ModuleManager::STATE_ACTIVE);
        $moduleManager->setModules(array_merge($configuration['modules'], array_keys($activeModules)));
        $moduleManager->loadModules();

        $listenersFromAppConfig = $configuration['listeners'] ?? [];
        $config = $serviceManager->get('Config');
        $listenersFromConfigService = $config['listeners'] ?? [];
        $listeners = array_unique(array_merge($listenersFromConfigService, $listenersFromAppConfig));
        return $serviceManager->get('Application')->bootstrap($listeners);
    }
}

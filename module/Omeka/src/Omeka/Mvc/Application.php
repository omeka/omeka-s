<?php
namespace Omeka\Mvc;

use Zend\Mvc\Application as ZendApplication;
use Zend\Mvc\Service;
use Zend\ServiceManager\ServiceManager;

/**
 * Application class for invoking the Omeka application.
 */
class Application extends ZendApplication
{
    /**
     * {@inheritDoc}
     */
    public static function init($configuration = array())
    {
        $smConfig = isset($configuration['service_manager']) ? $configuration['service_manager'] : array();
        $listeners = isset($configuration['listeners']) ? $configuration['listeners'] : array();
        $serviceManager = new ServiceManager(new Service\ServiceManagerConfig($smConfig));
        $serviceManager->setService('ApplicationConfig', $configuration);

        // Merge modules that are defined in configuration and those flagged
        // active in the database. Set all modules before loading them.
        $activeModules = $serviceManager->get('ActiveModules');
        $serviceManager
            ->get('ModuleManager')
            ->setModules(array_merge($configuration['modules'], $activeModules))
            ->loadModules();

        return $serviceManager->get('Application')->bootstrap($listeners);
    }
}

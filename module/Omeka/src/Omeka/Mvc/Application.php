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
        // Set all modules before loading them.
        $serviceManager->get('ModuleManager')
            ->setModules(self::getModules($serviceManager))->loadModules();
        return $serviceManager->get('Application')->bootstrap($listeners);
    }

    /**
     * Get all modules.
     *
     * Merges modules that are defined in configuration and those flagged active
     * in the database.
     *
     * @param ServiceManager $serviceManager
     * @return array
     */
    public static function getModules(ServiceManager $serviceManager)
    {
        $configuration = $serviceManager->get('ApplicationConfig');
        $connection = $serviceManager->get('Connection');
        $activeModules = array();
        return array_merge($configuration['modules'], $activeModules);
    }
}

<?php
namespace Omeka\Service;

use Omeka\Settings\Settings as Settings;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SettingsFactory implements FactoryInterface
{
    /**
     * Create the settings service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Settings
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $status = $serviceLocator->get('Omeka\Status');
        return new Settings($connection, $status);
    }
}

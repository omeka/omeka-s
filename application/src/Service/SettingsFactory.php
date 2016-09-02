<?php
namespace Omeka\Service;

use Omeka\Settings\Settings as Settings;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class SettingsFactory implements FactoryInterface
{
    /**
     * Create the settings service.
     *
     * @return Settings
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $status = $serviceLocator->get('Omeka\Status');
        return new Settings($connection, $status);
    }
}

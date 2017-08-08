<?php
namespace Omeka\Service\Settings;

use Omeka\Settings\Settings;
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
        return new Settings(
            $serviceLocator->get('Omeka\Connection'),
            $serviceLocator->get('Omeka\Status')
        );
    }
}

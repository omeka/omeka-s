<?php
namespace Omeka\Service\Settings;

use Omeka\Settings\PrioritySettings;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PrioritySettingsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new PrioritySettings(
            $serviceLocator->get('Omeka\Settings'),
            $serviceLocator->get('Omeka\Settings\Site'),
            $serviceLocator->get('Omeka\Settings\User')
        );
    }
}

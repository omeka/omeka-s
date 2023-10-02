<?php
namespace Omeka\Service\Settings;

use Omeka\Settings\FallbackSettings;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class FallbackSettingsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new FallbackSettings(
            $serviceLocator->get('Omeka\Settings'),
            $serviceLocator->get('Omeka\Settings\Site'),
            $serviceLocator->get('Omeka\Settings\User')
        );
    }
}

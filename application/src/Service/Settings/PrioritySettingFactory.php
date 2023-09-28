<?php
namespace Omeka\Service\Settings;

use Omeka\Settings\PrioritySetting;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class PrioritySettingFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new PrioritySetting(
            $serviceLocator->get('Omeka\Settings'),
            $serviceLocator->get('Omeka\Settings\Site'),
            $serviceLocator->get('Omeka\Settings\User')
        );
    }
}

<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\FallbackSettings;
use Laminas\ServiceManager\Factory\FactoryInterface;

class FallbackSettingsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new FallbackSettings($services->get('Omeka\Settings\Fallback'));
    }
}

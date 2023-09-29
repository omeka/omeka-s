<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\MultiSettings;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MultiSettingsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new MultiSettings($services->get('Omeka\Settings\Multi'));
    }
}

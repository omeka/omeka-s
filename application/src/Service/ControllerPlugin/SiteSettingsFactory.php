<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\Settings;
use Zend\ServiceManager\Factory\FactoryInterface;

class SiteSettingsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Settings($services->get('Omeka\Settings\Site'));
    }
}

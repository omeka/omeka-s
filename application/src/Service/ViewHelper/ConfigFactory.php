<?php
namespace Omeka\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Omeka\View\Helper\Config;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ConfigFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new Config($services->get('Config'));
    }
}

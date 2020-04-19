<?php
namespace Omeka\Service\ViewHelper;

use Interop\Container\ContainerInterface;
use Omeka\View\Helper\Logger;
use Laminas\ServiceManager\Factory\FactoryInterface;

class LoggerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Logger($services->get('Omeka\Logger'));
    }
}

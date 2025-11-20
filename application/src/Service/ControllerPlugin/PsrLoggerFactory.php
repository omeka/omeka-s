<?php
namespace Omeka\Service\ControllerPlugin;

use Interop\Container\ContainerInterface;
use Omeka\Mvc\Controller\Plugin\PsrLogger;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PsrLoggerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new PsrLogger($services->get('Omeka\PsrLogger'));
    }
}

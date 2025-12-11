<?php
namespace Omeka\Service;

use Laminas\Log\PsrLoggerAdapter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * PSR-3 Logger factory.
 */
class PsrLoggerFactory implements FactoryInterface
{
    /**
     * Create the logger service.
     *
     * @return PsrLoggerAdapter
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        return new PsrLoggerAdapter($serviceLocator->get('Omeka\Logger'));
    }
}

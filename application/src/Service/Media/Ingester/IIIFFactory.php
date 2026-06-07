<?php
namespace Omeka\Service\Media\Ingester;

use Omeka\Media\Ingester\IIIF;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class IIIFFactory implements FactoryInterface
{
    /**
     * Create the IIIF media ingester service.
     *
     * @return IIIF
     */
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $config = $services->get('Config');
        // The largest thumbnail constraint is the minimum size needed for thumbnail generation.
        $constraints = array_column($config['thumbnails']['types'] ?? [], 'constraint');
        $maxConstraint = $constraints ? max($constraints) : 800;
        return new IIIF(
            $services->get('Omeka\HttpClient'),
            $services->get('Omeka\File\Downloader'),
            $maxConstraint
        );
    }
}

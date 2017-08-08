<?php
namespace Omeka\Service\Media\Ingester;

use Omeka\Media\Ingester\IIIF;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class IIIFFactory implements FactoryInterface
{
    /**
     * Create the IIIF media ingester service.
     *
     * @return IIIF
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new IIIF(
            $services->get('Omeka\HttpClient'),
            $services->get('Omeka\File\Downloader')
        );
    }
}

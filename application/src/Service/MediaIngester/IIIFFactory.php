<?php
namespace Omeka\Service\MediaIngester;

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
        $httpClient = $services->get('Omeka\HttpClient');
        $fileManager = $services->get('Omeka\File\Manager');
        return new IIIF($httpClient, $fileManager);
    }
}

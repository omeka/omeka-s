<?php
namespace Omeka\Service\MediaIngester;

use Omeka\Media\Ingester\IIIF;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class IIIFFactory implements FactoryInterface
{
    /**
     * Create the IIIF media ingester service.
     *
     * @param ServiceLocatorInterface $mediaIngesterServiceLocator
     * @return IIIF
     */
    public function createService(ServiceLocatorInterface $mediaIngesterServiceLocator)
    {
        $serviceLocator = $mediaIngesterServiceLocator->getServiceLocator();
        $httpClient = $serviceLocator->get('Omeka\HttpClient');
        $fileManager = $serviceLocator->get('Omeka\File\Manager');
        return new IIIF($httpClient, $fileManager);
    }
}

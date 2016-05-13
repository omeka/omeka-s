<?php
namespace Omeka\Service\MediaIngester;

use Omeka\Media\Ingester\Url;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class UrlFactory implements FactoryInterface
{
    /**
     * Create the Url media ingester service.
     *
     * @param ServiceLocatorInterface $mediaIngesterServiceLocator
     * @return Url
     */
    public function createService(ServiceLocatorInterface $mediaIngesterServiceLocator)
    {
        $serviceLocator = $mediaIngesterServiceLocator->getServiceLocator();
        $fileManager = $serviceLocator->get('Omeka\File\Manager');
        return new Url($fileManager);
    }
}

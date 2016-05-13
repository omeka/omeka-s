<?php
namespace Omeka\Service\MediaIngester;

use Omeka\Media\Ingester\Youtube;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class YoutubeFactory implements FactoryInterface
{
    /**
     * Create the Youtube media ingester service.
     *
     * @param ServiceLocatorInterface $mediaIngesterServiceLocator
     * @return Youtube
     */
    public function createService(ServiceLocatorInterface $mediaIngesterServiceLocator)
    {
        $serviceLocator = $mediaIngesterServiceLocator->getServiceLocator();
        $fileManager = $serviceLocator->get('Omeka\File\Manager');
        return new Youtube($fileManager);
    }
}

<?php
namespace Omeka\Service;

use Omeka\Thumbnail\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

class ThumbnailManagerFactory implements FactoryInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Create the thumbnail manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Thumbnailer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $thumbnailer = $serviceLocator->get('Omeka\Thumbnailer');
        $config = $serviceLocator->get('Config')['thumbnails'];
        return new Manager($thumbnailer, $config);
    }
}

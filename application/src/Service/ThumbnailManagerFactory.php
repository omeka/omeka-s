<?php
namespace Omeka\Service;

use Omeka\Thumbnail\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ThumbnailManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config')['thumbnails'];
        return new Manager($config);
    }
}

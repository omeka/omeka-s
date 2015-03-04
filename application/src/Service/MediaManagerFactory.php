<?php
namespace Omeka\Service;

use Omeka\Api\Exception;
use Omeka\Media\Manager as MediaManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Media manager factory.
 */
class MediaManagerFactory implements FactoryInterface
{
    /**
     * Create the media manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return ApiManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        return new MediaManager(new Config($config['media_handlers']));
    }
}

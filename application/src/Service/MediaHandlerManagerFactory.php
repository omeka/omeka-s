<?php
namespace Omeka\Service;

use Omeka\Media\Handler\Manager;
use Omeka\Service\Exception;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Media manager factory.
 */
class MediaHandlerManagerFactory implements FactoryInterface
{
    /**
     * Create the media handler manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Manager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['media_handlers'])) {
            throw new Exception\ConfigException('Missing media handler configuration');
        }
        return new Manager(new Config($config['media_handlers']));
    }
}

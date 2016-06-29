<?php
namespace Omeka\Service;

use Omeka\Media\Ingester\Manager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MediaIngesterManagerFactory implements FactoryInterface
{
    /**
     * Create the media ingester manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Manager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['media_ingesters'])) {
            throw new Exception\ConfigException('Missing media ingester configuration');
        }
        $manager = new Manager(new Config($config['media_ingesters']));
        $manager->setServiceLocator($serviceLocator);
        return $manager;
    }
}

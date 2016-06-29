<?php
namespace Omeka\Service;

use Omeka\Site\BlockLayout\Manager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BlockLayoutManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['block_layouts'])) {
            throw new Exception\ConfigException('Missing block layout configuration');
        }
        $manager = new Manager(new Config($config['block_layouts']));
        $manager->setServiceLocator($serviceLocator);
        return $manager;
    }
}

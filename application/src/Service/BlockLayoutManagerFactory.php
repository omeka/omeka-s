<?php
namespace Omeka\Service;

use Omeka\BlockLayout\Manager;
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
        return new Manager(new Config($config['block_layouts']));
    }
}

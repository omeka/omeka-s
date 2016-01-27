<?php
namespace Omeka\Service;

use Omeka\DataType\Manager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class DataTypeManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['data_types'])) {
            throw new Exception\ConfigException('Missing data type configuration');
        }
        return new Manager(new Config($config['data_types']));
    }
}

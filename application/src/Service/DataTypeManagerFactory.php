<?php
namespace Omeka\Service;

use Omeka\DataType\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class DataTypeManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['data_types'])) {
            throw new Exception\ConfigException('Missing data type configuration');
        }
        return new Manager($serviceLocator, $config['data_types']);
    }
}

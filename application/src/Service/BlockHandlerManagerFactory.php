<?php
namespace Omeka\Service;

use Omeka\Block\Handler\Manager;
use Omeka\Service\Exception;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class BlockHandlerManagerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['block_handlers'])) {
            throw new Exception\ConfigException('Missing block handler configuration');
        }
        return new Manager(new Config($config['block_handlers']));
    }
}

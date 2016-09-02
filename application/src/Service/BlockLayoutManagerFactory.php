<?php
namespace Omeka\Service;

use Omeka\Site\BlockLayout\Manager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class BlockLayoutManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['block_layouts'])) {
            throw new Exception\ConfigException('Missing block layout configuration');
        }
        return new Manager($serviceLocator, $config['block_layouts']);
    }
}

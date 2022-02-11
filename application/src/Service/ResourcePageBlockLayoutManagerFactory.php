<?php
namespace Omeka\Service;

use Omeka\Site\ResourcePageBlockLayout\Manager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ResourcePageBlockLayoutManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        if (!isset($config['resource_page_block_layouts'])) {
            throw new Exception\ConfigException('Missing resource page block layout configuration');
        }
        return new Manager($services, $config['resource_page_block_layouts']);
    }
}

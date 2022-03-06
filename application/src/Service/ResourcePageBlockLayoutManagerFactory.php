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
        $manager = new Manager($services, $config['resource_page_block_layouts']);
        $manager->setResourcePageBlocksDefault($config['resource_page_blocks_default']);
        $manager->setSiteSettings($services->get('Omeka\Settings\Site'));
        return $manager;
    }
}

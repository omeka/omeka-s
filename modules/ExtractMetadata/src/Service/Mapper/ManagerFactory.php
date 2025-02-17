<?php
namespace ExtractMetadata\Service\Mapper;

use ExtractMetadata\Mapper\Manager;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        return new Manager($services, $config['extract_metadata_mappers']);
    }
}

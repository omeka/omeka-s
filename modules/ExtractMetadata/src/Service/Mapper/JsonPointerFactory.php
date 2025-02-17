<?php
namespace ExtractMetadata\Service\Mapper;

use ExtractMetadata\Mapper\JsonPointer;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class JsonPointerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $settings = $services->get('Omeka\Settings');
        $entityManager = $services->get('Omeka\EntityManager');
        return new JsonPointer($settings->get('extract_metadata_json_pointer_crosswalk', []), $entityManager);
    }
}

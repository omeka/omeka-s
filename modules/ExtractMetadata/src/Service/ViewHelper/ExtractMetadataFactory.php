<?php
namespace ExtractMetadata\Service\ViewHelper;

use ExtractMetadata\ViewHelper\ExtractMetadata;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ExtractMetadataFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ExtractMetadata($services);
    }
}

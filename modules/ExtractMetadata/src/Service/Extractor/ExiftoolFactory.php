<?php
namespace ExtractMetadata\Service\Extractor;

use ExtractMetadata\Extractor\Exiftool;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ExiftoolFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Exiftool($services->get('Omeka\Cli'));
    }
}

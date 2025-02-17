<?php
namespace ExtractMetadata\Service\Extractor;

use ExtractMetadata\Extractor\Tika;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class TikaFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $cli = $services->get('Omeka\Cli');
        $config = $services->get('Config');
        return new Tika($cli, $config['extract_metadata_extractor_config']['tika']);
    }
}

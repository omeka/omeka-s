<?php
namespace Mapping\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Mapping\Site\BlockLayout\Map;
use Mapping\Site\BlockLayout\MapQuery;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MapFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $htmlPurifier = $services->get('Omeka\HtmlPurifier');
        $moduleManager = $services->get('Omeka\ModuleManager');
        switch ($requestedName) {
            case 'mappingMapQuery':
                return new MapQuery($htmlPurifier, $moduleManager);
            default:
                return new Map($htmlPurifier, $moduleManager);
        }
    }
}

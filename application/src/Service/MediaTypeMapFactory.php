<?php
namespace Omeka\Service;

use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class MediaTypeMapFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $mapPath = OMEKA_PATH . '/application/data/media-types/media-type-map.php';
        if (!is_file($mapPath)) {
            throw new Exception\ConfigException('Missing media type/extension map file');
        }
        $map = include $mapPath;
        if (!is_array($map)) {
            throw new Exception\ConfigException('Invalid media type/extension map');
        }
        return $map;
    }
}

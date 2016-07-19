<?php
namespace Omeka\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MediaTypeMapFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
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

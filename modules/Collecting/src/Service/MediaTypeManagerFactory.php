<?php
namespace Collecting\Service;

use Collecting\MediaType\Manager;
use Interop\Container\ContainerInterface;
use Omeka\Service\Exception;
use Laminas\ServiceManager\Factory\FactoryInterface;

class MediaTypeManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['collecting_media_types'])) {
            throw new Exception\ConfigException('Missing collecting media type configuration');
        }
        return new Manager($serviceLocator, $config['collecting_media_types']);
    }
}

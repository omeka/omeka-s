<?php
namespace Omeka\Service;

use Omeka\Media\Renderer\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MediaRendererManagerFactory implements FactoryInterface
{
    /**
     * Create the media renderer manager service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Manager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['media_renderers'])) {
            throw new Exception\ConfigException('Missing media renderer configuration');
        }
        return new Manager($serviceLocator, $config['media_renderers']);
    }
}

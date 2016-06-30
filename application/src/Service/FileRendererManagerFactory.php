<?php
namespace Omeka\Service;

use Omeka\Media\FileRenderer\Manager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * File renderer manager factory.
 */
class FileRendererManagerFactory implements FactoryInterface
{
    /**
     * Create the file renderer manager service.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Manager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        if (!isset($config['file_renderers'])) {
            throw new Exception\ConfigException('Missing file renderer configuration');
        }
        return new Manager($serviceLocator, $config['file_renderers']);
    }
}

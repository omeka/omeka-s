<?php
namespace Omeka\Service;

use Omeka\Media\Handler\FileHandler;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FileHandlerFactory implements FactoryInterface
{
    /**
     * Create file media handler.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     * @return Connection
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $fileHandler = new FileHandler;

        $mediaTypeMap = include OMEKA_PATH . '/data/media-types/media-type-map.php';
        $fileHandler->setMediaTypeMap($mediaTypeMap);

        return $fileHandler;
    }
}

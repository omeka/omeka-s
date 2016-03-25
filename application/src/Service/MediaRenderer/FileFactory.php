<?php
namespace Omeka\Service\MediaRenderer;

use Omeka\Media\Renderer\File;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class FileFactory implements FactoryInterface
{
    /**
     * Create the File media renderer thumbnailer service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return File
     */
    public function createService(ServiceLocatorInterface $mediaRendererServiceLocator)
    {
        $serviceLocator = $mediaRendererServiceLocator->getServiceLocator();
        $fileRendererManager = $serviceLocator->get('Omeka\FileRendererManager');
        return new File($fileRendererManager);
    }
}

<?php
namespace Omeka\Service\FileThumbnailer;

use Omeka\File\Thumbnailer\ImageMagickThumbnailer;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ImageMagickFactory implements FactoryInterface
{
    /**
     * Create the ImageMagick thumbnailer service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ImageMagickThumbnailer
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $cli = $serviceLocator->get('Omeka\Cli');
        return new ImageMagickThumbnailer($cli);
    }
}

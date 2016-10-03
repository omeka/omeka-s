<?php
namespace Omeka\Service\FileThumbnailer;

use Interop\Container\ContainerInterface;
use Omeka\File\Thumbnailer\ImageMagickThumbnailer;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImageMagickFactory implements FactoryInterface
{
    /**
     * Create the ImageMagick thumbnailer service.
     *
     * @return ImageMagickThumbnailer
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ImageMagickThumbnailer($services->get('Omeka\Cli'));
    }
}

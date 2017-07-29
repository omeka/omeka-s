<?php
namespace Omeka\Service\FileThumbnailer;

use Interop\Container\ContainerInterface;
use Omeka\File\Thumbnailer\ImagickThumbnailer;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImagickFactory implements FactoryInterface
{
    /**
     * Create the ImageMagick thumbnailer service.
     *
     * @return ImageMagickThumbnailer
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ImagickThumbnailer($services->get('Omeka\File\TempFileFactory'));
    }
}

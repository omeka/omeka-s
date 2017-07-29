<?php
namespace Omeka\Service\File\Thumbnailer;

use Interop\Container\ContainerInterface;
use Omeka\File\Thumbnailer\ImageMagick;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImageMagickFactory implements FactoryInterface
{
    /**
     * Create the ImageMagick thumbnailer service.
     *
     * @return ImageMagick
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ImageMagick(
            $services->get('Omeka\Cli'),
            $services->get('Omeka\File\TempFileFactory')
        );
    }
}

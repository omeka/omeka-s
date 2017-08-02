<?php
namespace Omeka\Service\File\Thumbnailer;

use Interop\Container\ContainerInterface;
use Omeka\File\Thumbnailer\Imagick;
use Zend\ServiceManager\Factory\FactoryInterface;

class ImagickFactory implements FactoryInterface
{
    /**
     * Create the Imagick thumbnailer service.
     *
     * @return Imagick
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Imagick($services->get('Omeka\File\TempFileFactory'));
    }
}

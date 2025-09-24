<?php
namespace Omeka\Service\File\Thumbnailer;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\File\Thumbnailer\NoThumbnail;

class NoThumbnailFactory implements FactoryInterface
{
    /**
     * Create the NoThumbnail thumbnailer service.
     *
     * @return NoThumbnail
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new NoThumbnail();
    }
}

<?php
namespace Omeka\Service\File\Thumbnailer;

use Interop\Container\ContainerInterface;
use Omeka\File\Thumbnailer\NoThumbnail;
use Laminas\ServiceManager\Factory\FactoryInterface;

class NothumbnailFactory implements FactoryInterface
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

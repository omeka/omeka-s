<?php
namespace Omeka\Service\FileThumbnailer;

use Interop\Container\ContainerInterface;
use Omeka\File\Thumbnailer\GdThumbnailer;
use Zend\ServiceManager\Factory\FactoryInterface;

class GdFactory implements FactoryInterface
{
    /**
     * Create the GD thumbnailer service.
     *
     * @return GdThumbnailer
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new GdThumbnailer($services->get('Omeka\File\TempFileFactory'));
    }
}

<?php
namespace Omeka\Service\File\Thumbnailer;

use Interop\Container\ContainerInterface;
use Omeka\File\Thumbnailer\Gd;
use Zend\ServiceManager\Factory\FactoryInterface;

class GdFactory implements FactoryInterface
{
    /**
     * Create the GD thumbnailer service.
     *
     * @return Gd
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Gd($services->get('Omeka\File\TempFileFactory'));
    }
}

<?php
namespace Omeka\Service\File\Thumbnailer;

use Interop\Container\ContainerInterface;
use Omeka\File\Thumbnailer\GraphicsMagick;
use Zend\ServiceManager\Factory\FactoryInterface;

class GraphicsMagickFactory implements FactoryInterface
{
    /**
     * Create the GraphicsMagick thumbnailer service.
     *
     * @return GraphicsMagick
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new GraphicsMagick(
            $services->get('Omeka\Cli'),
            $services->get('Omeka\File\TempFileFactory')
        );
    }
}

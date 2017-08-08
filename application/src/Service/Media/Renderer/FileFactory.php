<?php
namespace Omeka\Service\Media\Renderer;

use Omeka\Media\Renderer\File;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class FileFactory implements FactoryInterface
{
    /**
     * Create the File media renderer thumbnailer service.
     *
     * @return File
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new File($services->get('Omeka\Media\FileRenderer\Manager'));
    }
}

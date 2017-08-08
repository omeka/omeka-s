<?php
namespace Omeka\Service\File;

use Omeka\File\Uploader;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UploaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Uploader($services->get('Omeka\File\TempFileFactory'));
    }
}

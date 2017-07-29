<?php
namespace Omeka\Service\File;

use Omeka\File\TempFileFactory;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class TempFileFactoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        return new TempFileFactory(
            $config['temp_dir'],
            $config['file_manager'],
            $services->get('Omeka\File\MediaTypeMap'),
            $services->get('Omeka\File\Manager')
        );
    }
}

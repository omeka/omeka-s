<?php
namespace Omeka\Service\File;

use Omeka\File\StagedFiles;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class StagedFilesFactory implements FactoryInterface
{
    /**
     * Create the files staged between requests.
     *
     * @return StagedFiles
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, ?array $options = null)
    {
        return new StagedFiles($serviceLocator->get('Config')['temp_dir']);
    }
}

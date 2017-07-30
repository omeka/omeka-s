<?php
namespace Omeka\Service\File;

use Omeka\File\Downloader;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class DownloaderFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        // Must pass the service locator because the Omeka\HttpClient service is
        // non-shared and must be reinstantiated for every call to download().
        return new Downloader(
            $services,
            $services->get('Omeka\File\TempFileFactory'),
            $services->get('Omeka\Logger')
        );
    }
}

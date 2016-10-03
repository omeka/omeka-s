<?php
namespace Omeka\Service\MediaIngester;

use Omeka\Media\Ingester\Upload;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UploadFactory implements FactoryInterface
{
    /**
     * Create the Upload media ingester service.
     *
     * @return Upload
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $fileManager = $services->get('Omeka\File\Manager');
        return new Upload($fileManager);
    }
}

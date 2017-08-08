<?php
namespace Omeka\Service\Media\Ingester;

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
        return new Upload(
            $services->get('Omeka\File\Validator'),
            $services->get('Omeka\File\Uploader')
        );
    }
}

<?php
namespace Omeka\Service\MediaIngester;

use Omeka\Media\Ingester\Url;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class UrlFactory implements FactoryInterface
{
    /**
     * Create the Url media ingester service.
     *
     * @return Url
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $fileManager = $services->get('Omeka\File\Manager');
        return new Url($fileManager);
    }
}

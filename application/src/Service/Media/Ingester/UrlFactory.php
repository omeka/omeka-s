<?php
namespace Omeka\Service\Media\Ingester;

use Omeka\Media\Ingester\Url;
use Laminas\ServiceManager\Factory\FactoryInterface;
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
        return new Url($services->get('Omeka\File\Downloader'));
    }
}

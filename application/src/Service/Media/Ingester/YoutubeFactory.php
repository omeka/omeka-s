<?php
namespace Omeka\Service\Media\Ingester;

use Omeka\Media\Ingester\Youtube;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class YoutubeFactory implements FactoryInterface
{
    /**
     * Create the Youtube media ingester service.
     *
     * @return Youtube
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Youtube(
            $services->get('Omeka\File\Downloader')
        );
    }
}

<?php
namespace Omeka\Service\MediaIngester;

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
        $fileManager = $services->get('Omeka\File\Manager');
        return new Youtube($fileManager);
    }
}

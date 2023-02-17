<?php
namespace Omeka\Service\Media\Ingester;

use Omeka\Media\Ingester\OEmbed;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class OEmbedFactory implements FactoryInterface
{
    /**
     * Create the oEmbed media ingester service.
     *
     * @return OEmbed
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        return new OEmbed(
            $services->get('Omeka\Oembed'),
            $services->get('Omeka\File\Downloader')
        );
    }
}

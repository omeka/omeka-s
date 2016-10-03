<?php
namespace Omeka\Service\MediaIngester;

use Omeka\Media\Ingester\OEmbed;
use Zend\ServiceManager\Factory\FactoryInterface;
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
        $whitelist = $config['oembed']['whitelist'];
        $httpClient = $services->get('Omeka\HttpClient');
        $fileManager = $services->get('Omeka\File\Manager');
        return new OEmbed($whitelist, $httpClient, $fileManager);
    }
}

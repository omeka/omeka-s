<?php
namespace Omeka\Service\MediaIngester;

use Omeka\Media\Ingester\OEmbed;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class OEmbedFactory implements FactoryInterface
{
    /**
     * Create the oEmbed media ingester service.
     *
     * @param ServiceLocatorInterface $mediaIngesterServiceLocator
     * @return OEmbed
     */
    public function createService(ServiceLocatorInterface $mediaIngesterServiceLocator)
    {
        $serviceLocator = $mediaIngesterServiceLocator->getServiceLocator();
        $config = $serviceLocator->get('Config');
        $whitelist = $config['oembed']['whitelist'];
        $httpClient = $serviceLocator->get('Omeka\HttpClient');
        $fileManager = $serviceLocator->get('Omeka\File\Manager');
        return new OEmbed($whitelist, $httpClient, $fileManager);
    }
}

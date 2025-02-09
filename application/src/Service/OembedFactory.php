<?php
namespace Omeka\Service;

use Omeka\Stdlib\Oembed;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class OembedFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        return new Oembed(
            $config['oembed']['whitelist'],
            $services->get('Omeka\HttpClient'),
            $services->get('MvcTranslator')
        );
    }
}

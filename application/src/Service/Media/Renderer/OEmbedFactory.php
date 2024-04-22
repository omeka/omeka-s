<?php
namespace Omeka\Service\Media\Renderer;

use Omeka\Media\Renderer\OEmbed;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class OEmbedFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new OEmbed($services->get('Omeka\Oembed'));
    }
}

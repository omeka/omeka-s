<?php
namespace Omeka\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Omeka\Site\BlockLayout\Oembed;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OembedFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Oembed($services->get('Omeka\Oembed'));
    }
}

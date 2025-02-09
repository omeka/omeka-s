<?php
namespace Omeka\Service\Media\Ingester;

use Omeka\Media\Ingester\IiifPresentation;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class IiifPresentationFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new IiifPresentation(
            $services->get('Omeka\HttpClient'),
            $services->get('Omeka\File\Downloader')
        );
    }
}

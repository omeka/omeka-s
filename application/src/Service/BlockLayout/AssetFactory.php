<?php
namespace Omeka\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Omeka\Site\BlockLayout\Asset;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AssetFactory implements FactoryInterface
{
    /**
     * Create the asset block layout service.
     *
     * @param ContainerInterface $serviceLocator
     * @return Asset
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new Asset();
    }
}

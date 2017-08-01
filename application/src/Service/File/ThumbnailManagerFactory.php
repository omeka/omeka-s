<?php
namespace Omeka\Service\File;

use Omeka\File\ThumbnailManager;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ThumbnailManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ThumbnailManager($services);
    }
}

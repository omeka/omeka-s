<?php
namespace Omeka\Service\File\Thumbnailer;

use Omeka\File\Thumbnailer\ThumbnailerFactory;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ThumbnailerFactoryFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ThumbnailerFactory($services);
    }
}

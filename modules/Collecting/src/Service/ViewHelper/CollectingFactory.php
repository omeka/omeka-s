<?php
namespace Collecting\Service\ViewHelper;

use Collecting\View\Helper\Collecting;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CollectingFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Collecting(
            $services->get('Collecting\MediaTypeManager'),
            $services->get('Omeka\ModuleManager')
        );
    }
}

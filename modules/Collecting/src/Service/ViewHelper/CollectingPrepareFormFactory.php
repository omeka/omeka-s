<?php
namespace Collecting\Service\ViewHelper;

use Collecting\View\Helper\CollectingPrepareForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class CollectingPrepareFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new CollectingPrepareForm($services->get('Collecting\MediaTypeManager'));
    }
}

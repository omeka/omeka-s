<?php
namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\OptionSidebar;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class OptionSidebarFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        return new OptionSidebar(
            $services->get('Omeka\OptionSidebar'),
            $services->get('FormElementManager')
        );
    }
}

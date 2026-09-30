<?php
namespace Omeka\Service;

use Omeka\Stdlib\OptionSidebar;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class OptionSidebarFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, ?array $options = null)
    {
        $config = $services->get('Config');
        $managers = [];
        foreach ($config['option_sidebars'] ?? [] as $key => $spec) {
            $managers[$key] = $services->get($spec['manager']);
        }
        return new OptionSidebar(
            $config,
            $managers,
            $services->get('Omeka\ModuleManager'),
            $services->get('Omeka\Settings\Fallback'),
            $services->get('Omeka\Settings'),
            $services->get('Omeka\Settings\Site'),
            $services->get('Omeka\Settings\User'),
            $services->get('MvcTranslator')
        );
    }
}

<?php
namespace Omeka\Service;

use Omeka\Stdlib\Browse;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class BrowseFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $config = $services->get('Config');
        return new Browse(
            $config['column_defaults'],
            $config['browse_defaults'],
            $config['sort_defaults'],
            $services->get('Omeka\ColumnTypeManager'),
            $services->get('ViewHelperManager'),
            $services->get('EventManager'),
            $services->get('Omeka\Settings\User'),
            $services->get('Omeka\Settings\Site')
        );
    }
}

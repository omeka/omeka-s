<?php
namespace Omeka\Service\ColumnType;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Omeka\ColumnType\Theme;

class ThemeFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new Theme($services->get('Omeka\Site\ThemeManager'));
    }
}

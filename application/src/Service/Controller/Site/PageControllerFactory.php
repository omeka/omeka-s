<?php
namespace Omeka\Service\Controller\Site;

use Interop\Container\ContainerInterface;
use Omeka\Controller\Site\PageController;
use Laminas\ServiceManager\Factory\FactoryInterface;

class PageControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $currentTheme = $services->get('Omeka\Site\ThemeManager')->getCurrentTheme();
        return new PageController($currentTheme);
    }
}

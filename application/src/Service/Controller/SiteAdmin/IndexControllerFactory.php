<?php
namespace Omeka\Service\Controller\SiteAdmin;

use Interop\Container\ContainerInterface;
use Omeka\Controller\SiteAdmin\IndexController;
use Zend\ServiceManager\Factory\FactoryInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new IndexController(
            $services->get('Omeka\Site\ThemeManager'),
            $services->get('Omeka\Site\NavigationLinkManager'),
            $services->get('Omeka\Site\NavigationTranslator')
        );
    }
}

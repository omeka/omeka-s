<?php
namespace Omeka\Service\Controller\SiteAdmin;

use Omeka\Controller\SiteAdmin\IndexController;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class IndexControllerFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $controllers)
    {
        $services = $controllers->getServiceLocator();
        return new IndexController(
            $services->get('Omeka\Site\ThemeManager'),
            $services->get('Omeka\Site\NavigationLinkManager'),
            $services->get('Omeka\Site\NavigationTranslator')
        );
    }
}

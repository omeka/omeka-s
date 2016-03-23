<?php
namespace Omeka\Service;

use Omeka\Site\Navigation\Translator;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SiteNavigationTranslatorFactory implements FactoryInterface
{
    /**
     * Create the Site\Navigation\Translator service.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Translator
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new Translator($serviceLocator->get('Omeka\Site\NavigationLinkManager'));
    }
}

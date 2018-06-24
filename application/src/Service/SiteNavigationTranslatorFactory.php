<?php
namespace Omeka\Service;

use Interop\Container\ContainerInterface;
use Omeka\Site\Navigation\Translator;
use Zend\ServiceManager\Factory\FactoryInterface;

class SiteNavigationTranslatorFactory implements FactoryInterface
{
    /**
     * Create the Site\Navigation\Translator service.
     *
     * @return Translator
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        return new Translator(
            $serviceLocator->get('Omeka\Site\NavigationLinkManager'),
            $serviceLocator->get('MvcTranslator'),
            $serviceLocator->get('ViewHelperManager')->get('Url')
        );
    }
}

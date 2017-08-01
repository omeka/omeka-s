<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\Locale;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class LocaleFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $locale = null;
        $routeMatch = $services->get('Application')->getMvcEvent()->getRouteMatch();
        if ($routeMatch->getParam('__SITE__')) {
            // Use the site's locale if currently in a site.
            $locale = $services->get('Omeka\SiteSettings')->get('locale');
        }
        if (!$locale) {
            // Use the global locale as default.
            $config = $services->get('Config');
            $locale = $config['translator']['locale'];
        }
        return new Locale($locale);
    }
}

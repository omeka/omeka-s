<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\ThemeSetting;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the assetUrl view helper.
 */
class ThemeSettingFactory implements FactoryInterface
{
    /**
     * Create and return the assetUrl view helper
     *
     * @param ServiceLocatorInterface $viewServiceLocator
     * @return ApiJsonStrategy
     */
    public function createService(ServiceLocatorInterface $viewServiceLocator)
    {
        $serviceLocator = $viewServiceLocator->getServiceLocator();
        $currentTheme = $serviceLocator->get('Omeka\Site\ThemeManager')->getCurrentTheme();
        $siteSettings = $serviceLocator->get('Omeka\SiteSettings');

        $themeSettings = $siteSettings->get($currentTheme->getSettingsKey(), []);
        return new ThemeSetting($themeSettings);
    }
}

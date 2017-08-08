<?php

namespace Omeka\Service\ViewHelper;

use Omeka\View\Helper\ThemeSetting;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the themeSetting view helper.
 */
class ThemeSettingFactory implements FactoryInterface
{
    /**
     * Create and return the themeSetting view helper
     *
     * @return ThemeSetting
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $currentTheme = $services->get('Omeka\Site\ThemeManager')->getCurrentTheme();
        $siteSettings = $services->get('Omeka\Settings\Site');

        $themeSettings = $siteSettings->get($currentTheme->getSettingsKey(), []);
        return new ThemeSetting($themeSettings);
    }
}

<?php

namespace Omeka\Service\ViewHelper;

use Omeka\Module\Manager as ModuleManager;
use Omeka\View\Helper\AssetUrl;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * Service factory for the assetUrl view helper.
 */
class AssetUrlFactory implements FactoryInterface
{
    /**
     * Create and return the assetUrl view helper
     *
     * @return ApiJsonStrategy
     */
    public function __invoke(ContainerInterface $serviceLocator, $requestedName, array $options = null)
    {
        $currentTheme = $serviceLocator->get('Omeka\Site\ThemeManager')->getCurrentTheme();
        $activeModules = $serviceLocator->get('Omeka\ModuleManager')
            ->getModulesByState(ModuleManager::STATE_ACTIVE);

        $assetConfig = $serviceLocator->get('Config')['assets'];
        if ($assetConfig['use_externals']) {
            $externals = $assetConfig['externals'];
        } else {
            $externals = [];
        }

        $helper = new AssetUrl($currentTheme, $activeModules, $externals);
        return $helper;
    }
}

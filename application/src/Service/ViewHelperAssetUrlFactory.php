<?php

namespace Omeka\Service;

use Omeka\Module\Manager as ModuleManager;
use Omeka\View\Helper\AssetUrl;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Service factory for the assetUrl view helper.
 */
class ViewHelperAssetUrlFactory implements FactoryInterface
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
        $activeModules = $serviceLocator->get('Omeka\ModuleManager')
            ->getModulesByState(ModuleManager::STATE_ACTIVE);

        $assetConfig = $serviceLocator->get('Config')['assets'];
        if ($assetConfig['use_externals']) {
            $externals = $assetConfig['externals'];
        } else {
            $externals = array();
        }

        $helper = new AssetUrl($currentTheme, $activeModules, $externals);
        return $helper;
    }
}

<?php
namespace Omeka\View\Helper;

use Omeka\Service\ThemeManager;
use Omeka\Module\Manager as ModuleManager;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHtmlElement;

class AssetUrl extends AbstractHtmlElement
{
    const OMEKA_ASSETS_PATH = '%s/application/asset/%s';
    const MODULE_ASSETS_PATH = '%s/modules/%s/asset/%s';
    const THEME_ASSETS_PATH = '%s/themes/%s/assets/%s';

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var array Cached array of all active modules
     */
    protected $activeModules;

    /**
     * Construct the helper.
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->themeManager = $serviceLocator->get('Omeka\ThemeManager');
        $this->moduleManager = $serviceLocator->get('Omeka\ModuleManager');
        $this->activeModules = $this->moduleManager
            ->getModulesByState(ModuleManager::STATE_ACTIVE);
    }

    /**
     * Return a path to an asset.
     *
     * Returns the asset URL for the current theme if no module specified.
     * Otherwise, returns the asset URL for the specified module, only if the
     * module is active. Does not check if the asset file exists.
     *
     * @param string $file
     * @param string|null $module
     * @return string|null
     */
    public function __invoke($file, $module = null)
    {
        $basePath = $this->getView()->basePath();
        if (null === $module) {
            $theme = $this->themeManager->getCurrent();
            return sprintf(self::THEME_ASSETS_PATH, $basePath, $theme, $file);
        }
        if ('Omeka' == $module) {
            return sprintf(self::OMEKA_ASSETS_PATH, $basePath, $file);
        }
        if (array_key_exists($module, $this->activeModules)) {
            return sprintf(self::MODULE_ASSETS_PATH, $basePath, $module, $file);
        }
        return null;
    }
}

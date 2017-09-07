<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

/**
 * View helper for returning a path to an asset.
 */
class AssetUrl extends AbstractHelper
{
    const OMEKA_ASSETS_PATH = '%s/application/asset/%s';
    const MODULE_ASSETS_PATH = '%s/modules/%s/asset/%s';
    const THEME_ASSETS_PATH = '%s/themes/%s/asset/%s';

    /**
     * @var Theme The current theme, if any
     */
    protected $currentTheme;

    /**
     * @var array Array of all active modules
     */
    protected $activeModules;

    /**
     * @var array Array of all external overrides to use for asset URLs
     */
    protected $externals;

    /**
     * Construct the helper.
     *
     * @param string|null $currentTheme
     * @param array $modules
     */
    public function __construct($currentTheme, $modules, $externals)
    {
        $this->currentTheme = $currentTheme;
        $this->activeModules = $modules;
        $this->externals = $externals;
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
        if (isset($this->externals[$module][$file])) {
            return $this->externals[$module][$file];
        }

        $basePath = $this->getView()->basePath();
        if (null === $module && $this->currentTheme) {
            return sprintf(self::THEME_ASSETS_PATH, $basePath,
                $this->currentTheme->getId(), $file);
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

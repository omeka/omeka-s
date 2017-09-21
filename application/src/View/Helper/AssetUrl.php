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
     * @param Theme|null $currentTheme
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
     * Otherwise, the url depends on whether an override is allowed or not.
     * - If an override is allowed and if the module is set and active, returns
     * the asset URL for the current theme if the file exists, else returns the
     * asset URL for the specified module (without checking if the asset file
     * exists in it, because it’s a prerequisite). If the module is disabled,
     * returns null.
     * - If it is not allowed, returns the asset URL for the specified module,
     * only if the module is active. Does not check if the asset file exists.
     *
     * In all cases, if the module is set and if the file is marked as external,
     * the external url will be returned by priority, without any check.
     *
     * @param string $file
     * @param string|null $module
     * @param bool $override
     * @return string|null
     */
    public function __invoke($file, $module = null, $override = false)
    {
        if (isset($this->externals[$module][$file])) {
            return $this->externals[$module][$file];
        }

        $basePath = $this->getView()->basePath();
        if (null === $module && $this->currentTheme) {
            return sprintf(self::THEME_ASSETS_PATH, $basePath,
                $this->currentTheme->getId(), $file);
        }

        if ($override && $this->currentTheme
            && ($module === 'Omeka' || array_key_exists($module, $this->activeModules))
        ) {
            $themeId = $this->currentTheme->getId();
            $filepath = sprintf(self::THEME_ASSETS_PATH, OMEKA_PATH, $themeId, $file);
            if (is_readable($filepath)) {
                return sprintf(self::THEME_ASSETS_PATH, $basePath, $themeId, $file);
            }
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

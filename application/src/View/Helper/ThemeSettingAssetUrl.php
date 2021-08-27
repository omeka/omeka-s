<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for getting a path to a theme setting asset.
 */
class ThemeSettingAssetUrl extends AbstractHelper
{
    /**
     * Get a path to a theme setting asset.
     *
     * @param string $id
     * @param string|null $default
     * @return string|null
     */
    public function __invoke($id, $default = null)
    {
        $view = $this->getView();

        $asset = $view->themeSettingAsset($id);

        if ($asset === null) {
            return $default;
        }

        return $asset->assetUrl();
    }
}

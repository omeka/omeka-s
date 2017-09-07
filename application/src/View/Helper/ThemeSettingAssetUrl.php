<?php
namespace Omeka\View\Helper;

use Omeka\Api\Exception as ApiException;
use Zend\View\Helper\AbstractHelper;

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

        $setting = $view->themeSetting($id);

        if ($setting === null) {
            return $default;
        }

        try {
            $response = $view->api()->read('assets', $setting);
        } catch (ApiException\NotFoundException $e) {
            return $default;
        }

        return $response->getContent()->assetUrl();
    }
}

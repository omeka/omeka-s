<?php
namespace Omeka\View\Helper;

use Omeka\Api\Exception as ApiException;
use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for getting an asset from a theme setting
 */
class ThemeSettingAsset extends AbstractHelper
{
    /**
     * Get an asset representation from a theme setting.
     *
     * @param string $id
     * @return \Omeka\Api\Representation\AssetRepresentation|null
     */
    public function __invoke($id)
    {
        $view = $this->getView();

        $setting = $view->themeSetting($id);

        if ($setting === null) {
            return null;
        }

        try {
            $response = $view->api()->read('assets', $setting);
        } catch (ApiException\NotFoundException $e) {
            return null;
        }

        return $response->getContent();
    }
}

<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for rendering the settings filter.
 *
 * The filter narrows the settings in the element it is rendered into, by text
 * and by group. Groups are the .settings-group elements there, which the
 * formCollectionSettingsGroups helper renders.
 */
class SettingsFilter extends AbstractHelper
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/settings-filter';

    /**
     * Render the settings filter.
     *
     * @return string
     */
    public function __invoke()
    {
        $view = $this->getView();
        $view->headScript()->appendFile($view->assetUrl('js/settings-filter.js', 'Omeka'));
        return $view->partial(self::PARTIAL_NAME);
    }
}

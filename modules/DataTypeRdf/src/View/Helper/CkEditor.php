<?php declare(strict_types=1);

namespace DataTypeRdf\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for loading scripts necessary to use CKEditor on a page.
 *
 * Override core view helper to load a specific config.
 *
 * Used in various modules:
 * @see \Omeka\View\Helper\CkEditor
 * @see \BlockPlus\View\Helper\CkEditor
 * @see \DataTypeRdf\View\Helper\CkEditor
 */
class CkEditor extends AbstractHelper
{
    /**
     * Load the scripts necessary to use CKEditor on a page.
     */
    public function __invoke(): void
    {
        static $loaded;

        if (!is_null($loaded)) {
            return;
        }

        $loaded = true;

        $view = $this->getView();
        $plugins = $view->getHelperPluginManager();
        $assetUrl = $plugins->get('assetUrl');
        $escapeJs = $plugins->get('escapeJs');
        $params = $view->params();

        $isAdmin = $params->fromRoute('__ADMIN__');
        $isSiteAdmin = $params->fromRoute('__SITEADMIN__');
        $controller = $params->fromRoute('__CONTROLLER__');
        $action = $params->fromRoute('action');

        // Check if module BlockPlus is available.
        $hasBlockPlus = $plugins->has('pageMetadata');

        $isSiteAdminPage = $isSiteAdmin
            && $hasBlockPlus
            && ($controller === 'Page' || $controller === 'page')
            && $action === 'edit';

        $isSiteAdminResource = $isAdmin
            && in_array($controller, ['Item', 'ItemSet', 'Media', 'Annotation', 'item', 'item-set', 'media', 'annotation'])
            && ($action === 'edit' || $action === 'add');

        $script = '';
        if ($isSiteAdminPage || $isSiteAdminResource) {
            $setting = $plugins->get('setting');
            $pageOrResource = $isSiteAdminPage ? 'page' : 'resource';
            $module = $isSiteAdminPage ? 'blockplus' : 'datatyperdf';
            $htmlMode = $setting($module . '_html_mode_' . $pageOrResource);
            if ($htmlMode && $htmlMode !== 'inline') {
                $script = <<<JS
CKEDITOR.config.customHtmlMode = '$htmlMode';

JS;
            }

            if ($hasBlockPlus) {
                $htmlConfig = $setting($module . '_html_config_' . $pageOrResource);
                $customConfigJs = $htmlConfig && $htmlConfig !== 'default'
                    ? 'js/ckeditor_config_' . $htmlConfig . '.js'
                    : 'js/ckeditor_config.js';
                $customConfigUrl = $escapeJs($assetUrl($customConfigJs, 'BlockPlus'));
            }
        }

        $customConfigUrl ??= $escapeJs($assetUrl('js/ckeditor_config.js', 'DataTypeRdf'));

        $script .= <<<JS
CKEDITOR.config.customConfig = '$customConfigUrl';
JS;

        // The footnotes icon is not loaded automaically, so add css.
        // Only this css rule is needed.
        // The js for data-type-rdf is already loaded with the data types.
        $view->headLink()
            ->appendStylesheet($assetUrl('css/data-type-rdf.css', 'DataTypeRdf'));

        $view->headScript()
            // Don't use defer for now.
            ->appendFile($assetUrl('vendor/ckeditor/ckeditor.js', 'Omeka'))
            ->appendFile($assetUrl('vendor/ckeditor-footnotes/footnotes/plugin.js', 'DataTypeRdf'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($assetUrl('vendor/ckeditor/adapters/jquery.js', 'Omeka'))
            ->appendScript($script);
    }
}

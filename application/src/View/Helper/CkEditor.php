<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for loading scripts necessary to use CKEditor on a page.
 */
class CkEditor extends AbstractHelper
{
    /**
     * Load the scripts necessary to use CKEditor on a page.
     */
    public function __invoke()
    {
        $view = $this->getView();
        $customConfigUrl = $view->escapeJs($view->assetUrl('js/ckeditor_config.js', 'Omeka'));
        $view->headScript()
            ->appendFile($view->assetUrl('vendor/ckeditor/ckeditor.js', 'Omeka'), 'text/javascript', ['defer' => 'defer'])
            ->appendFile($view->assetUrl('vendor/ckeditor/adapters/jquery.js', 'Omeka'), 'text/javascript', ['defer' => 'defer'])
            ->appendScript('window.addEventListener("DOMContentLoaded", function() {
    (function($) {
        CKEDITOR.config.customConfig = "' . $customConfigUrl . '";
    })(jQuery);
});');
    }
}

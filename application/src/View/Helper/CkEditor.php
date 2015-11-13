<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class CkEditor extends AbstractHelper
{
    /**
     * Load the scripts necessary to use CKEditor on a page.
     */
    public function __invoke()
    {
        $view = $this->getView();
        $customConfigUrl = $view->escapeJs($view->assetUrl('js/ckeditor_config.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/ckeditor/ckeditor.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/ckeditor/adapters/jquery.js', 'Omeka'));
        $view->headScript()->appendScript("CKEDITOR.config.customConfig = '$customConfigUrl'");
    }
}

<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class CkEditor extends AbstractHelper
{
    public function __invoke()
    {
        $view = $this->getView();

        // Load the scripts necessary to use CKEditor on a page.
        $customConfigUrl = $view->escapeJs($view->assetUrl('js/ckeditor_config.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/ckeditor/ckeditor.js', 'Omeka'));
        $view->headScript()->appendFile($view->assetUrl('js/ckeditor/adapters/jquery.js', 'Omeka'));
        $view->headScript()->appendScript("CKEDITOR.config.customConfig = '$customConfigUrl'");

        // Map the CKEditor element types to the view helpers that render them.
        $view->formElement()->addType('ckeditor', 'formCkeditor');
        $view->formElement()->addType('ckeditor_inline', 'formCkeditorInline');
    }
}

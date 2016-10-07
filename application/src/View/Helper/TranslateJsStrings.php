<?php
namespace Omeka\View\Helper;

use Zend\View\Helper\AbstractHelper;

class TranslateJsStrings extends AbstractHelper
{
    /**
     * Provide translations for JavaScript strings.
     *
     * Attach to the "js.translate_strings" event to add strings to be
     * translated. Use the `jsTranslate(str)` function to interpolate translated
     * strings in JS.
     */
    public function __invoke()
    {
        $view = $this->getView();
        $params = $view->trigger('js.translate_strings', ['strings' => []], true);
        $jsTranslations = [];
        foreach ($params['strings'] as $jsString) {
            $jsTranslations[$jsString] = $view->translate($jsString);
        }
        $view->headScript()->prependScript(sprintf('
var jsTranslations = %s;
var jsTranslate = function(str) {
    return (str in jsTranslations) ? jsTranslations[str] : str;
};', json_encode($jsTranslations)));
    }
}

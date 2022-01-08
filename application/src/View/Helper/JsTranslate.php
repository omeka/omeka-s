<?php
namespace Omeka\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for rendering translations for JavaScript strings.
 */
class JsTranslate extends AbstractHelper
{
    protected $jsTranslations;

    /**
     * Construct the helper.
     *
     * @param array $jsTranslations
     */
    public function __construct(array $jsTranslations)
    {
        $this->jsTranslations = $jsTranslations;
    }

    /**
     * Render translations for JavaScript strings.
     *
     * Add to the "js_translate_strings" module config to add strings to be
     * translated. Use the `Omeka.jsTranslate(str)` function to interpolate
     * translated strings in JS.
     */
    public function __invoke()
    {
        $this->getView()->headScript()->appendScript(sprintf('
window.addEventListener("DOMContentLoaded", function() {
    (function($) {
        Omeka.jsTranslate = function(str) {
            var jsTranslations = %s;
            return (str in jsTranslations) ? jsTranslations[str] : str;
        };
    })(jQuery);
});
', json_encode($this->jsTranslations)));
    }
}

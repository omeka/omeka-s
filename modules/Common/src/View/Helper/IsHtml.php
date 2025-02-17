<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper to check if a string is a well-formed html.
 *
 * @see \DataTypeRdf\DataType\Html::isWellFormed()
 */
class IsHtml extends AbstractHelper
{
    /**
     * Check if a string is a well-formed html with start/end tags for any part.
     * Don't check validity or security.
     *
     * Because php < 8.4 does not manage html 5, the check is limited to the
     * html produced by the ckeditor included in Omeka.
     *
     * Support strings without a root tag, but require a tag for each fragment
     * of the string. So a simple string is not html, even if it contains valid
     * html tags.
     * @see https://www.w3.org/TR/rdf11-concepts/#section-html
     *
     * @example `<span>This is a <strong>valid</strong> html for this function.</span>`
     * @example `This is <strong>not</strong> a valid html for this function.`
     * @example `This is not a valid html for this function.`
     */
    public function __invoke($string): bool
    {
        if (!$string) {
            return false;
        }

        // Skip non scalar, except stringable object.
        if (!is_scalar($string)
            && !(is_object($string) && method_exists($string, '__toString'))
        ) {
            return false;
        }

        $string = trim((string) $string);

        // Do some quick checks.
        if (!$string
            || mb_substr($string, 0, 1) !== '<'
            || mb_substr($string, -1) !== '>'
            // TODO Is it really a quick check to use strip_tags before simplexml?
            || $string === strip_tags($string)
        ) {
            return false;
        }

        return true;

        /*
        // With CKeditor or CodeMirror, the root node is not required, so append
        // one. Anyway, it is required by the specification for html fragment.
        // False is already returned above for simple strings.
        // For Html, it should be html.
        // Normally, libXml add missing html/body, etc., except with flags
        // LIBXML_HTML_NOIMPLIED and LIBXML_HTML_NODEFDTD,
        // but it work better when manually added.
        if (mb_substr($string, 0, 2) !== '<?'
            && mb_substr($string, 0, 2) !== '<!'
            && mb_substr($string, 0, 5) !== '<html'
            && mb_substr($string, -7) !== '</html>'
            && mb_substr($string, 0, 5) !== '<body'
            && mb_substr($string, -7) !== '</body>'
        ) {
            $string = '<html><body>' . $string . '</body></html>';
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $simpleXml = simplexml_load_string(
            html_entity_decode($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401),
            'SimpleXMLElement',
            LIBXML_COMPACT | LIBXML_NONET
        );

        return $simpleXml !== false
            && !count(libxml_get_errors());
        */
    }
}

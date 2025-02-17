<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * View helper to check if a string is a well-formed xml.
 *
 * @see \DataTypeRdf\DataType\Xml::isWellFormed()
 */
class IsXml extends AbstractHelper
{
    /**
     * Check if a string is a well-formed xml. Don't check validity or security.
     *
     * Require a root tag, according to the w3c spec for the lexical space of
     * the data type rdf:XMLLiteral, that is the set of all strings which are
     * well-balanced and self-contained XML content.
     * @see https://www.w3.org/TR/rdf11-concepts/#section-XMLLiteral
     *
     * For html fragment, the lexical space is larger (any unicode string), so
     * this method does more checks than needed.
     * @see https://www.w3.org/TR/rdf11-concepts/#section-html
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

        // A root is required, so the first tag must be the same than the last.
        // Namespaces are not managed, as the specs indicates that it can be
        // specified by the wrapper of the fragment.
        if (mb_substr($string, 0, 5) !== '<?xml') {
            $tag = mb_substr($string, 1, min(mb_strpos($string, ' ') ?: mb_strlen($string), mb_strpos($string, '>')) - 1);
            if (mb_substr($string, - mb_strlen($tag) - 3) !== "</$tag>") {
                return false;
            }
        }

        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $simpleXml = simplexml_load_string(
            html_entity_decode($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401),
            'SimpleXMLElement',
            LIBXML_COMPACT | LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        return $simpleXml !== false
            && !count(libxml_get_errors());
    }
}

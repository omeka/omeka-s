<?php
namespace Omeka\Stdlib;

use SplQueue;
use Zend\Json\Json;

/**
 * Class for encoding to and decoding from JSON.
 */
class JsonUnescaped extends Json
{
    /**
     * Copy of the parent method to allow to access to parent private methods.
     *
     * @inheritdoc
     */
    public static function encode($valueToEncode, $cycleCheck = false, array $options = [])
    {
        if (is_object($valueToEncode)) {
            if (method_exists($valueToEncode, 'toJson')) {
                return $valueToEncode->toJson();
            }

            if (method_exists($valueToEncode, 'toArray')) {
                return static::encode($valueToEncode->toArray(), $cycleCheck, $options);
            }
        }

        // Pre-process and replace javascript expressions with placeholders
        $javascriptExpressions = new SplQueue();
        if (isset($options['enableJsonExprFinder'])
            && $options['enableJsonExprFinder'] == true
        ) {
            $valueToEncode = static::recursiveJsonExprFinder($valueToEncode, $javascriptExpressions);
        }

        // Encoding
        $prettyPrint = (isset($options['prettyPrint']) && ($options['prettyPrint'] === true));
        $encodedResult = self::encodeValue($valueToEncode, $cycleCheck, $options, $prettyPrint);

        // Post-process to revert back any Zend\Json\Expr instances.
        $encodedResult = self::injectJavascriptExpressions($encodedResult, $javascriptExpressions);

        return $encodedResult;
    }

    /**
     * Copy of the parent method to allow to access to parent private methods.
     * @see \Zend\Json\Json::encodeValue()
     *
     * Encode a value to JSON.
     *
     * Intermediary step between injecting JavaScript expressions.
     *
     * Delegates to either the PHP built-in json_encode operation, or the
     * Encoder component, based on availability of the built-in and/or whether
     * or not the component encoder is requested.
     *
     * @param mixed $valueToEncode
     * @param bool $cycleCheck
     * @param array $options
     * @param bool $prettyPrint
     * @return string
     */
    private static function encodeValue($valueToEncode, $cycleCheck, array $options, $prettyPrint)
    {
        if (function_exists('json_encode') && static::$useBuiltinEncoderDecoder !== true) {
            return self::encodeViaPhpBuiltIn($valueToEncode, $prettyPrint);
        }

        return self::encodeViaEncoder($valueToEncode, $cycleCheck, $options, $prettyPrint);
    }

    /**
     * Encode a value to JSON using the PHP built-in json_encode function.
     *
     * Unlike the private parent method, it never escapes tag, apostrophes,
     * quotes ampersand, unicode character, slashes, and ends of line: it is not
     * required by the recommandation.
     *
     * @see \Zend\Json\Json::encodeViaPhpBuiltIn()
     *
     * If $prettyPrint is boolean true, also uses JSON_PRETTY_PRINT.
     *
     * @param mixed $valueToEncode
     * @param bool $prettyPrint
     * @return string|false Boolean false return value if json_encode is not
     *     available, or the $useBuiltinEncoderDecoder flag is enabled.
     */
    private static function encodeViaPhpBuiltIn($valueToEncode, $prettyPrint = false)
    {
        if (! function_exists('json_encode') || static::$useBuiltinEncoderDecoder === true) {
            return false;
        }

        $encodeOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS;
        if ($prettyPrint) {
            $encodeOptions |= JSON_PRETTY_PRINT;
        }

        return json_encode($valueToEncode, $encodeOptions);
    }

    /**
     * Copy of private parent method.
     * @see \Zend\Json\Json::injectJavascriptExpressions()
     *
     * Inject javascript expressions into the encoded value.
     *
     * Loops through each, substituting the "magicKey" of each with its
     * associated value.
     *
     * @param string $encodedValue
     * @param SplQueue $javascriptExpressions
     * @return string
     */
    private static function injectJavascriptExpressions($encodedValue, SplQueue $javascriptExpressions)
    {
        foreach ($javascriptExpressions as $expression) {
            $encodedValue = str_replace(
                sprintf('"%s"', $expression['magicKey']),
                $expression['value'],
                (string) $encodedValue
            );
        }

        return $encodedValue;
    }
}

<?php declare(strict_types=1);

namespace Omeka\Stdlib;

/**
 * Interpolate a PSR-3 message with a context into a string.
 */
trait PsrInterpolateTrait
{
    /**
     * Interpolates context values into the PSR-3 message placeholders.
     *
     * Keys that are not stringable are kept as class or type.
     *
     * @see https://www.php-fig.org/psr/psr-3/
     *
     * @param string $message Message with PSR-3 placeholders.
     * @param array $context Associative array with placeholders and strings.
     * @return string
     */
    public function interpolate($message, ?array $context = null): string
    {
        $message = (string) $message;

        if (empty($context)) {
            return $message;
        }

        if (strpos($message, '{') === false) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            $placeholder = '{' . $key . '}';
            if (is_null($val)
                || is_scalar($val)
                || (is_object($val) && method_exists($val, '__toString'))
            ) {
                $replacements[$placeholder] = $val;
            } elseif (is_array($val)) {
                $replacements[$placeholder] = 'array' . @json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            } elseif (is_object($val)) {
                $replacements[$placeholder] = '[object ' . get_class($val) . ']';
            } elseif (is_resource($val)) {
                $replacements[$placeholder] = '[resource ' . get_resource_type($val) . ']';
            } else {
                $replacements[$placeholder] = '[' . gettype($val) . ']';
            }
        }

        return strtr($message, $replacements);
    }
}

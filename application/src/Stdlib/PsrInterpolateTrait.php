<?php

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
    public function interpolate($message, array $context = [])
    {
        if (empty($context)) {
            return $message;
        }

        $message = (string) $message;
        if (strpos($message, '{') === false) {
            return $message;
        }

        $replacements = [];
        foreach ($context as $key => $val) {
            if (is_null($val)
                || is_scalar($val)
                || (is_object($val) && method_exists($val, '__toString'))
            ) {
                $replacements['{' . $key . '}'] = $val;
            } elseif (is_array($val)) {
                $replacements['{' . $key . '}'] = 'array' . @json_encode($val);
            } elseif (is_object($val)) {
                $replacements['{' . $key . '}'] = '[object ' . get_class($val) . ']';
            } elseif (is_resource($val)) {
                $replacements['{' . $key . '}'] = '[resource ' . get_resource_type($val) . ']';
            } else {
                $replacements['{' . $key . '}'] = '[' . gettype($val) . ']';
            }
        }

        return strtr($message, $replacements);
    }
}

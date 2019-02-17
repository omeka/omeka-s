<?php

namespace Omeka\Stdlib;

/**
 * Interpolate a PSR-3 message with a context into a string.
 */
interface PsrInterpolateInterface
{
    /**
     * Interpolates context values into the PSR-3 message placeholders.
     *
     * Keys that are not stringable are kept as class or type.
     *
     * @param string $message Message with PSR-3 placeholders.
     * @param array $context Associative array with placeholders and strings.
     * @return string
     */
    public function interpolate($message, array $context = null);
}

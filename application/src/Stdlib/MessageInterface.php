<?php
namespace Omeka\Stdlib;

/**
 * Message interface.
 */
interface MessageInterface
{
    /**
     * Get the message string.
     *
     * @return string
     */
    public function getMessage();

    /**
     * Get the context of the message (the arguments, if any).
     *
     * @return array
     */
    public function getContext();

    /**
     * Does this message have a context (arguments)?
     *
     * @return bool
     */
    public function hasContext();

    /**
     * Indicate if the message should be escaped for html.
     *
     * @return bool
     */
    public function escapeHtml();

    /**
     * Get the interpolated message string with context.
     *
     * @param string $message
     * @param array $context
     * @return string
     */
    public function interpolate($message, array $context = []);
}

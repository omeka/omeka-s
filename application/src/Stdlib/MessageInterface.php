<?php
namespace Omeka\Stdlib;

use JsonSerializable;
use Laminas\I18n\Translator\TranslatorInterface;

/**
 * Message interface.
 */
interface MessageInterface extends JsonSerializable
{
    /**
     * Indicate if the message should be escaped for html.
     *
     * @return bool
     */
    public function escapeHtml();

    /**
     * Get the interpolated message
     */
    public function __toString();

    /**
     * Get the interpolated message, translated
     */
    public function translate(TranslatorInterface $translator, $textDomain = 'default', $locale = null);
}

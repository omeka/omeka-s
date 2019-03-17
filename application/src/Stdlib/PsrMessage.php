<?php

namespace Omeka\Stdlib;

use Zend\I18n\Translator\TranslatorAwareInterface;
use Zend\I18n\Translator\TranslatorAwareTrait;

/**
 * Manage a message with a list of placeholders formatted as psr-3.
 *
 * It is similar to Message, except the constructor, that requires an array, and
 * the possibility to translate automatically when the translator is enabled.
 * Generally, the translator is not set, as it is usually managed internally.
 *
 * ```
 * // To get a translator in a controller:
 * $translator = $this->getEvent()->getApplication()->getServiceManager()->get('MvcTranslator');
 * // or:
 * $translator = $this->viewHelpers()->get('translate')->getTranslator();
 *
 * // To get translator in a view:
 * $translator = $this->plugin('translate')->getTranslator();
 *
 * // To set the translator:
 * $psrMessage->setTranslator($translator);
 * // To disable the translation when the translator is set:
 * $psrMessage->setTranslatorEnabled(false);
 * ```
 *
 * @see \Omeka\Stdlib\Message
 */
class PsrMessage implements MessageInterface, TranslatorAwareInterface, \JsonSerializable, PsrInterpolateInterface
{
    use PsrInterpolateTrait;
    use TranslatorAwareTrait;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $context;

    /**
     * @var bool
     */
    protected $escapeHtml = true;

    /**
     * Set the message string and its context. The plural is not managed.
     *
     * @param string $message
     * @param array $context
     */
    public function __construct($message, array $context = [])
    {
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Get the message string.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the message context.
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Does this message have context?
     *
     * @return bool
     */
    public function hasContext()
    {
        return (bool) $this->context;
    }

    public function setEscapeHtml($escapeHtml)
    {
        $this->escapeHtml = (bool) $escapeHtml;
        return $this;
    }

    public function escapeHtml()
    {
        return $this->escapeHtml;
    }

    public function __toString()
    {
        return $this->isTranslatorEnabled()
            ? $this->translate()
            : $this->interpolate($this->getMessage(), $this->getContext());
    }

    /**
     * Translate the message with the context.
     *
     * Same as TranslatorInterface::translate(), but the message is the current one.
     *
     * @param string $textDomain
     * @param string $locale
     * @return string
     */
    public function translate($textDomain = 'default', $locale = null)
    {
        return $this->hasTranslator()
            ? $this->interpolate($this->translator->translate($this->getMessage(), $textDomain, $locale), $this->getContext())
            : $this->interpolate($this->getMessage(), $this->getContext());
    }

    public function jsonSerialize()
    {
        return (string) $this;
    }
}

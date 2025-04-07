<?php declare(strict_types=1);

namespace Omeka\Stdlib;

use Laminas\I18n\Translator\TranslatorInterface;

/**
 * Message with a context list of placeholders formatted as psr-3.
 *
 * @see \Omeka\Stdlib\Message
 */
class PsrMessage implements \JsonSerializable, MessageInterface, PsrInterpolateInterface
{
    use PsrInterpolateTrait;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $context = [];

    /**
     * @var bool
     */
    protected $escapeHtml = true;

    /**
     * Set the message string and its context. The plural is not managed.
     */
    public function __construct($message, array $context = [])
    {
        $this->message = $message;
        $this->context = $context;
    }

    /**
     * Get the message string.
     */
    public function getMessage(): string
    {
        return (string) $this->message;
    }

    /**
     * Get the message context.
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Does this message have context?
     */
    public function hasContext(): bool
    {
        return (bool) $this->context;
    }

    public function setEscapeHtml($escapeHtml): self
    {
        $this->escapeHtml = (bool) $escapeHtml;
        return $this;
    }

    /**
     * Get the flag escapeHtml.
     */
    public function getEscapeHtml(): bool
    {
        return $this->escapeHtml;
    }

    /**
     * Get the flag escapeHtml. Kept for compatibility.
     *
     * @deprecated This is a getter, so use getEscapeHtml().
     */
    public function escapeHtml()
    {
        return $this->escapeHtml;
    }

    public function __toString()
    {
        return $this->interpolate($this->getMessage(), $this->getContext());
    }

    /**
     * Translate the message with the context.
     *
     * Same as TranslatorInterface::translate(), but the message is the current one.
     */
    public function translate(TranslatorInterface $translator, $textDomain = 'default', $locale = null)
    {
        return $this->interpolate($translator->translate($this->getMessage(), $textDomain, $locale), $this->getContext());
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}

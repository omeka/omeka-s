<?php
namespace Omeka\Stdlib;

use Laminas\I18n\Translator\TranslatorInterface;

/**
 * Message with a a list of placeholders formatted for sprintf().
 */
class Message implements MessageInterface
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var bool
     */
    protected $escapeHtml = true;

    /**
     * Set the message string and its arguments.
     *
     * @param string $message
     * @param string $args,...
     */
    public function __construct($message, ...$args)
    {
        $this->message = $message;
        $this->args = $args;
    }

    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the message arguments.
     *
     * @deprecated 1.4.0 Use getContext() instead.
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Does this message have arguments?
     *
     * @deprecated 1.4.0 Use hasContext() instead.
     * @return bool
     */
    public function hasArgs()
    {
        return (bool) $this->args;
    }

    public function setEscapeHtml($escapeHtml)
    {
        $this->escapeHtml = (bool) $escapeHtml;
    }

    public function escapeHtml()
    {
        return $this->escapeHtml;
    }

    public function interpolate($message, array $context = [])
    {
        return (string) sprintf($message, ...$context);
    }

    public function __toString()
    {
        return $this->interpolate($this->getMessage(), $this->getArgs());
    }

    public function translate(TranslatorInterface $translator, $textDomain = 'default', $locale = null)
    {
        return $this->interpolate($translator->translate($this->getMessage(), $textDomain, $locale), $this->getArgs());
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }
}

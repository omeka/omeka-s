<?php
namespace Omeka\Stdlib;

/**
 * Manage a message with a a list of placeholders formatted for sprintf().
 */
class Message implements MessageInterface, \JsonSerializable
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

    public function getContext()
    {
        return $this->args;
    }

    public function hasContext()
    {
        return (bool) $this->args;
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
        return $this->interpolate($this->getMessage(), $this->getContext());
    }

    public function jsonSerialize()
    {
        return (string) $this;
    }
}

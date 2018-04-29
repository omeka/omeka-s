<?php
namespace Omeka\Stdlib;

class Message implements \JsonSerializable
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
     * Get the message arguments.
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Does this message have arguments?
     *
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

    public function __toString()
    {
        return (string) sprintf($this->getMessage(), ...$this->getArgs());
    }

    public function jsonSerialize()
    {
        return (string) $this;
    }
}

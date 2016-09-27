<?php
namespace Omeka\Stdlib;

class Message
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
}

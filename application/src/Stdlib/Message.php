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
     * @param string $arg1
     * @param string $arg2
     * @param ...
     */
    public function __construct($message)
    {
        $args = func_get_args();
        $this->message = array_shift($args);
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

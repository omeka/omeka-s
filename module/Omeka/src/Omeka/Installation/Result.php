<?php
namespace Omeka\Installation;

class Result
{
    const MESSAGE_TYPE_INFO = 'info';
    const MESSAGE_TYPE_ERROR = 'error';
    const MESSAGE_TYPE_WARNING = 'warning';

    /**
     * @var array
     */
    protected $validMessageTypes = array(
        self::MESSAGE_TYPE_INFO,
        self::MESSAGE_TYPE_ERROR,
        self::MESSAGE_TYPE_WARNING,
    );

    /**
     * @var array
     */
    protected $messages = array();

    /**
     * @var bool
     */
    protected $isError = false;

    /**
     * Add an installation message.
     *
     * @param string $message The message.
     * @param string $type The type of message.
     * @param string $task The installation task that's adding the message.
     */
    public function addMessage($message, $type, $task)
    {
        if (!in_array($type, $this->validMessageTypes)) {
            $type = self::MESSAGE_TYPE_INFO;
        }
        $this->messages[$task][$type][] = $message;
        // One error message sets this as an error result.
        if (self::MESSAGE_TYPE_ERROR == $type) {
            $this->isError = true;
        }
    }

    /**
     * Get all messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Check whether this is an error result.
     *
     * @return bool
     */
    public function isError()
    {
        return (bool) $this->isError;
    }
}

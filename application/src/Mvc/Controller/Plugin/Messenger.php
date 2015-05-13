<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;

/**
 * Implement session-based messages.
 */
class Messenger extends AbstractPlugin
{
    const ERROR   = 0;
    const SUCCESS = 1;
    const WARNING = 2;
    const NOTICE  = 3;

    /**
     * @var Container
     */
    protected $container;

    /**
     * Construct the messenger controller plugin.
     */
    public function __construct()
    {
        $this->container = new Container('OmekaMessenger');
    }

    /**
     * Add a message by type.
     *
     * @param string $type
     * @param string $message
     */
    public function add($type, $message)
    {
        if (!isset($this->container->messages)) {
            $this->container->messages = array();
        }
        $this->container->messages[$type][] = $message;
    }

    /**
     * Add an error message.
     *
     * @param string $message
     */
    public function addError($message)
    {
        $this->add(self::ERROR, $message);
    }

    public function addErrors(array $messages)
    {
        foreach ($messages as $message) {
            if (is_array($message)) {
                $this->addErrors($message);
            } else {
                $this->addError($message);
            }
        }
    }

    /**
     * Add a success message.
     *
     * @param string $message
     */
    public function addSuccess($message)
    {
        $this->add(self::SUCCESS, $message);
    }

    /**
     * Add a warning message.
     *
     * @param string $message
     */
    public function addWarning($message)
    {
        $this->add(self::WARNING, $message);
    }

    /**
     * Add a notice message.
     *
     * @param string $message
     */
    public function addNotice($message)
    {
        $this->add(self::NOTICE, $message);
    }

    /**
     * Get all messages.
     *
     * @return array
     */
    public function get()
    {
        if (!isset($this->container->messages)) {
            return array();
        }
        return $this->container->messages;
    }

    /**
     * Clear all messages.
     */
    public function clear()
    {
        unset($this->container->messages);
    }
}

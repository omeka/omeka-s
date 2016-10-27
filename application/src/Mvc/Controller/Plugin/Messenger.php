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
     * @param string $key
     */
    public function add($type, $message, $key = null)
    {
        if (!isset($this->container->messages)) {
            $this->container->messages = [];
        }
        $this->container->messages[$type][] = [$message, $key];
    }

    /**
     * Add an error message.
     *
     * @param string $message
     * @param string $key
     */
    public function addError($message, $key = null)
    {
        $this->add(self::ERROR, $message, $key);
    }

    /**
     * Add multiple errors at once.
     *
     * This method accepts arbitrarily-deeply nested arrays of messages.
     *
     * @param array $messages
     */
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

    public function addFormErrors(\Zend\Form\Form $form)
    {
        $allMessages = $form->getMessages();
        foreach ($allMessages as $elementName => $messages) {
            $elementLabel = $form->get($elementName)->getLabel();
            foreach ($messages as $message) {
                $this->addError($message, $elementLabel);
            }
        }
    }

    /**
     * Add a success message.
     *
     * @param string $message
     * @param array $args
     */
    public function addSuccess($message)
    {
        $this->add(self::SUCCESS, $message);
    }

    /**
     * Add a warning message.
     *
     * @param string $message
     * @param array $args
     */
    public function addWarning($message)
    {
        $this->add(self::WARNING, $message);
    }

    /**
     * Add a notice message.
     *
     * @param string $message
     * @param array $args
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
            return [];
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

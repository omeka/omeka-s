<?php
namespace Omeka\Service;

use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Mail\Message;
use Zend\Mail\MessageFactory;
use Zend\Mail\Transport\TransportInterface;

class Mailer
{
    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var array
     */
    protected $defaultOptions;

    /**
     * Set the transport and message defaults.
     *
     * @var TransportInterface $transport
     * @var array $defaultOptions
     */
    public function __construct(TransportInterface $transport,
        array $defaultOptions = array()
    ) {
        $this->transport = $transport;
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * Get the transport.
     *
     * @return TransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Return a new message object.
     *
     * @param array|Traversable $options
     * @return Message
     */
    public function createMessage($options = array())
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        foreach ($this->defaultOptions as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }
        return MessageFactory::getInstance($options);
    }

    /**
     * Send a message using the configured transport.
     *
     * @param array|Traversable|Message $message
     */
    public function send($message)
    {
        if ($message instanceof Message) {
            $this->transport->send($message);
        } else {
            $this->transport->send($this->createMessage($message));
        }
    }
}

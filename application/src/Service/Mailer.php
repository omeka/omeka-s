<?php
namespace Omeka\Service;

use Traversable;
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
     * Set the transport.
     *
     * @var TransportInterface $transport
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
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
     * Return a message object.
     *
     * @param array|Traversable $options
     * @return Message
     */
    public function getMessage($options = array())
    {
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
            $this->transport->send($this->getMessage($message));
        }
    }
}

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
     * Return a new message object.
     *
     * By default, encode messages as UTF-8.
     *
     * @param array|Traversable $options
     * @return Message
     */
    public function createMessage($options = array())
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        if (!isset($options['encoding'])) {
            $options['encoding'] = 'UTF-8';
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

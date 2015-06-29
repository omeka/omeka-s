<?php
namespace Omeka\Service;

use Omeka\Entity\User;
use Omeka\Entity\UserActivation;
use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Mail\Message;
use Zend\Mail\MessageFactory;
use Zend\Mail\Transport\TransportInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class Mailer implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

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
     * Sets default options if not already set.
     *
     * @param array|Traversable $options
     * @return Message
     */
    public function createMessage($options = array())
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        }
        $options = array_merge($this->defaultOptions, $options);
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

    /**
     * Send a create password message.
     *
     * @param User $user
     * @param string $subject
     * @param string $body
     * @param bool $activate Whether to activate the user after setting a new
     * password
     */
    public function sendCreatePassword(User $user, $subject, $body, $activate = true) {
        $serviceLocator = $this->getServiceLocator();

        $userActivation = new UserActivation;
        $userActivation->setId();
        $userActivation->setUser($user);
        $userActivation->setActivate($activate);
        $entityManager = $serviceLocator->get('Omeka\EntityManager');
        $entityManager->persist($userActivation);
        $entityManager->flush();

        $activationUrl = $serviceLocator->get('ControllerPluginManager')
            ->get('Url')->fromRoute(
                'activate',
                array('key' => $userActivation->getId()),
                array('force_canonical' => true)
            );
        $message = $this->createMessage();
        $message->addTo($user->getEmail(), $user->getName())
            ->setSubject($subject)
            ->setBody(sprintf($body, $activationUrl));
        $this->send($message);
    }
}

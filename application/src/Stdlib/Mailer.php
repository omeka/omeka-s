<?php
namespace Omeka\Stdlib;

use Doctrine\ORM\EntityManager;
use Omeka\Entity\User;
use Omeka\Entity\PasswordCreation;
use Traversable;
use Zend\Stdlib\ArrayUtils;
use Zend\Mail\Message;
use Zend\Mail\MessageFactory;
use Zend\Mail\Transport\TransportInterface;
use Zend\View\HelperPluginManager;

class Mailer
{
    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var HelperPluginManager
     */
    protected $viewHelpers;

    /**
     * @var EntityManager
     */
    protected $entityManager;

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
    public function __construct(TransportInterface $transport, HelperPluginManager $viewHelpers,
        EntityManager $entityManager, array $defaultOptions = []
    ) {
        $this->transport = $transport;
        $this->viewHelpers = $viewHelpers;
        $this->entityManager = $entityManager;
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
    public function createMessage($options = [])
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
     * Create and return a password creation entity.
     *
     * @param User $user
     * @param bool $activate Whether to activate the user after setting a new
     *  password
     * @return PasswordCreation
     */
    public function getPasswordCreation(User $user, $activate)
    {
        $passwordCreation = new PasswordCreation;
        $passwordCreation->setId();
        $passwordCreation->setUser($user);
        $passwordCreation->setActivate($activate);

        $this->entityManager->persist($passwordCreation);
        $this->entityManager->flush();
        return $passwordCreation;
    }

    /**
     * Return an absolute URL to the create password page.
     *
     * @param PasswordCreation $passwordCreation
     * @return string
     */
    public function getCreatePasswordUrl(PasswordCreation $passwordCreation)
    {
        $url = $this->viewHelpers->get('url');
        return $url(
                'create-password',
                ['key' => $passwordCreation->getId()],
                ['force_canonical' => true]
            );
    }

    /**
     * Return an absolute URL to the main public page.
     *
     * @return string
     */
    public function getSiteUrl()
    {
        $url = $this->viewHelpers->get('url');
        return $url('top', [], ['force_canonical' => true]);
    }

    /**
     * Return the expiration date.
     *
     * @param PasswordCreation $passwordCreation
     * @return string
     */
    public function getExpiration(PasswordCreation $passwordCreation)
    {
        return $this->viewHelpers
            ->get('i18n')->dateFormat($passwordCreation->getExpiration(), 'medium', 'medium');
    }

    /**
     * Return the title of this Omeka S installation.
     *
     * @return string
     */
    public function getInstallationTitle()
    {
        $setting = $this->viewHelpers->get('setting');
        return $setting('installation_title', 'Omeka S');
    }

    /**
     * Send a reset password email.
     *
     * @param User $user
     */
    public function sendResetPassword(User $user)
    {
        $translate = $this->viewHelpers->get('translate');
        $installationTitle = $this->getInstallationTitle();
        $template = $translate('Greetings, %1$s!

It seems you have forgotten your password for %5$s at %2$s

To reset your password, click this link:
%3$s

Your reset link will expire on %4$s.');

        $passwordCreation = $this->getPasswordCreation($user, false);
        $body = sprintf(
            $template,
            $user->getName(),
            $this->getSiteUrl(),
            $this->getCreatePasswordUrl($passwordCreation),
            $this->getExpiration($passwordCreation),
            $installationTitle
        );

        $message = $this->createMessage();
        $message->addTo($user->getEmail(), $user->getName())
            ->setSubject(sprintf(
                $translate('Reset your password for %s'),
                $installationTitle
            ))
            ->setBody($body);
        $this->send($message);
    }

    /**
     * Send a user activation email.
     *
     * @param User $user
     */
    public function sendUserActivation(User $user)
    {
        $translate = $this->viewHelpers->get('translate');
        $installationTitle = $this->getInstallationTitle();
        $template = $translate('Greetings!

A user has been created for you on %5$s at %1$s

Your username is your email: %2$s

Click this link to set a password and begin using Omeka S:
%3$s

Your activation link will expire on %4$s. If you have not completed the user activation process by the time the link expires, you will need to request another activation email from your site administrator.');

        $passwordCreation = $this->getPasswordCreation($user, true);
        $body = sprintf(
            $template,
            $this->getSiteUrl(),
            $user->getEmail(),
            $this->getCreatePasswordUrl($passwordCreation),
            $this->getExpiration($passwordCreation),
            $installationTitle
        );

        $message = $this->createMessage();
        $message->addTo($user->getEmail(), $user->getName())
            ->setSubject(sprintf(
                $translate('User activation for %s'),
                $installationTitle
            ))
            ->setBody($body);
        $this->send($message);
    }
}

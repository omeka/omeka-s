<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Stdlib\Mailer as MailerService;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin for getting the mailer service.
 */
class Mailer extends AbstractPlugin
{
    /**
     * @var MailerService
     */
    protected $mailer;

    /**
     * Construct the plugin.
     *
     * @param MailerService $mailer
     */
    public function __construct(MailerService $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Get the mailer service.
     *
     * @return MailerService
     */
    public function __invoke()
    {
        return $this->mailer;
    }
}

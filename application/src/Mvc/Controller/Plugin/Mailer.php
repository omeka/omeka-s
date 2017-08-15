<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Stdlib\Mailer as MailerService;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Mailer extends AbstractPlugin
{
    /**
     * @var MailerService
     */
    protected $mailer;

    public function __construct(MailerService $mailer)
    {
        $this->mailer = $mailer;
    }

    public function __invoke()
    {
        return $this->mailer;
    }
}

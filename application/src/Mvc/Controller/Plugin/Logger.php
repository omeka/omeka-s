<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Log\LoggerInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Logger extends AbstractPlugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger
     *
     * Since this returns the Logger itself, all the normal logging methods are available.
     *
     * @return LoggerInterface
     */
    public function __invoke()
    {
        return $this->logger;
    }
}

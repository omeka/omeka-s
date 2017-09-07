<?php
namespace Omeka\Mvc\Controller\Plugin;

use Zend\Log\LoggerInterface;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin for getting the logger.
 */
class Logger extends AbstractPlugin
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Construct the plugin.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger
     *
     * @return LoggerInterface
     */
    public function __invoke()
    {
        return $this->logger;
    }
}

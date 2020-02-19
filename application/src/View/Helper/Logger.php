<?php
namespace Omeka\View\Helper;

use Laminas\Log\LoggerInterface;
use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for getting the Zend logger.
 */
class Logger extends AbstractHelper
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Construct the helper.
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

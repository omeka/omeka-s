<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Job\Dispatcher;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin for getting the job dispatcher.
 */
class JobDispatcher extends AbstractPlugin
{
    /**
     * @var Dispatcher
     */
    protected $jobDispatcher;

    /**
     * Construct the plugin.
     *
     * @param Dispatcher $jobDispatcher
     */
    public function __construct(Dispatcher $jobDispatcher)
    {
        $this->jobDispatcher = $jobDispatcher;
    }

    /**
     * Get the job dispatcher
     *
     * @return Dispatcher
     */
    public function __invoke()
    {
        return $this->jobDispatcher;
    }
}

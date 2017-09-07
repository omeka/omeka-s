<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Mvc\Status as StatusService;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

/**
 * Controller plugin for getting the status service.
 */
class Status extends AbstractPlugin
{
    /**
     * @var StatusService
     */
    protected $statusService;

    /**
     * Construct the plugin.
     *
     * @param StatusService $statusService
     */
    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Get the status service.
     *
     * @return StatusService
     */
    public function __invoke()
    {
        return $this->statusService;
    }
}

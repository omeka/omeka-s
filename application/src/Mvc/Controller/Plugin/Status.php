<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Mvc\Status as StatusService;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Status extends AbstractPlugin
{
    /**
     * @var StatusService
     */
    protected $statusService;

    public function __construct(StatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    public function __invoke()
    {
        return $this->statusService;
    }
}

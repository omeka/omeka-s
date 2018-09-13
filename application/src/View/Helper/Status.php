<?php
namespace Omeka\View\Helper;

use Omeka\Mvc\Status as MvcStatus;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for getting MVC status.
 */
class Status extends AbstractHelper
{
    /**
     * @var MvcStatus
     */
    protected $status;

    /**
     * Construct the helper.
     *
     * @param MvcStatus $status
     */
    public function __construct(MvcStatus $status)
    {
        $this->status = $status;
    }

    /**
     * Check whether the current HTTP request is an API request.
     *
     * @return bool
     */
    public function isApiRequest()
    {
        return $this->status->isApiRequest();
    }

    /**
     * Check whether the current HTTP request is an admin request.
     *
     * @return bool
     */
    public function isAdminRequest()
    {
        return $this->status->isAdminRequest();
    }

    /**
     * Check whether the current HTTP request is a site request.
     *
     * @return bool
     */
    public function isSiteRequest()
    {
        return $this->status->isSiteRequest();
    }
}

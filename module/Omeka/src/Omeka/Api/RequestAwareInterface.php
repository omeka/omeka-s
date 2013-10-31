<?php
namespace Omeka\Api;

use Omeka\Api\Request;

/**
 * Request-aware interface.
 */
interface RequestAwareInterface
{
    /**
     * Set the API request.
     *
     * @param Request $request
     */
    public function setRequest(Request $request);

    /**
     * Get the API request.
     *
     * @return Request
     */
    public function getRequest();
}

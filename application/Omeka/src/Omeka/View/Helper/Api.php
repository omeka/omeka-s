<?php
namespace Omeka\View\Helper;

use Omeka\Api\Manager as ApiManager;
use Zend\View\Helper\AbstractHelper;

/**
 * Helper for direct access to API read and search operations.
 */
class Api extends AbstractHelper
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    /**
     * Construct the helper.
     *
     * @param ApiManager $apiManager
     */
    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * Execute a search API request.
     *
     * @param string $resource
     * @param mixed $data
     * @return Response
     */
    public function search($resource, array $data = array())
    {
        return $this->apiManager->search($resource, $data);
    }

    /**
     * Execute a read API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param mixed $data
     * @return Response
     */
    public function read($resource, $id, array $data = array())
    {
        return $this->apiManager->read($resource, $id, $data);
    }
}

<?php
namespace Omeka\View\Helper;

use Omeka\Api\Manager as ApiManager;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for direct access to API read and search operations.
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
    public function search($resource, $data = [])
    {
        return $this->apiManager->search($resource, $data);
    }

    /**
     * Execute a search API request and get the first result.
     *
     * Sets the first result to the response content or null if there is no
     * result. Note that this functionality is not native to the API.
     *
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function searchOne($resource, $data = [])
    {
        $data['limit'] = 1;
        $response = $this->apiManager->search($resource, $data);
        $content = $response->getContent();
        $content = is_array($content) && count($content) ? $content[0] : null;
        $response->setContent($content);
        return $response;
    }

    /**
     * Execute a read API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param mixed $data
     * @return Response
     */
    public function read($resource, $id, $data = [])
    {
        return $this->apiManager->read($resource, $id, $data);
    }
}

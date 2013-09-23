<?php
namespace Omeka\Api;

use Omeka\Api\Request;

/**
 * Api response.
 */
class Response
{
    /**
     * @var mixed
     */
    protected $response;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Construct the API response.
     *
     * @param mixed $response
     * @param null|Request $request
     */
    public function __construct($response = null, Request $request = null)
    {
        if (null !== $response) {
            $this->response = $response;
        }
        if (null !== $request) {
            $this->request = $request;
        }
    }

    /**
     * Set the response data.
     *
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * Get the response data.
     * 
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set the request of this response.
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the request of this response.
     * 
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}

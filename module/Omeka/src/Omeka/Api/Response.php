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
    protected $data;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Construct the API response.
     *
     * @param mixed $data
     * @param null|Request $request
     */
    public function __construct($data = null)
    {
        if (null !== $data) {
            $this->data = $data;
        }
    }

    /**
     * Set the response data.
     *
     * @param mixed $response
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get the response data.
     * 
     * @return string
     */
    public function getData()
    {
        return $this->data;
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

<?php
namespace Omeka\Api;

/**
 * Api response.
 */
class Response
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var int
     */
    protected $totalResults;

    /**
     * @var mixed
     */
    protected $content;

    /**
     * Construct the API response.
     *
     * @param mixed $data
     */
    public function __construct($content = null)
    {
        if (null !== $content) {
            $this->setContent($content);
        }
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

    /**
     * Set the total results of this response.
     *
     * @param int
     */
    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
    }

    /**
     * Get the total results of this response.
     *
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * Set request content.
     *
     * @param mixed $value
     */
    public function setContent($value)
    {
        $this->content = $value;
    }

    /**
     * Get request content.
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
}

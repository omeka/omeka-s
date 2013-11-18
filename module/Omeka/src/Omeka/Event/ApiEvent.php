<?php
namespace Omeka\Event;

use Omeka\Api\Request;
use Omeka\Api\Response;
use Zend\EventManager\Event;

/**
 * API event.
 */
class ApiEvent extends Event
{
    const EVENT_EXECUTE_PRE  = 'execute.pre';
    const EVENT_EXECUTE_POST = 'execute.post';
    const EVENT_SEARCH_PRE   = 'search.pre';
    const EVENT_SEARCH_POST  = 'search.post';
    const EVENT_CREATE_PRE   = 'create.pre';
    const EVENT_CREATE_POST  = 'create.post';
    const EVENT_READ_PRE     = 'read.pre';
    const EVENT_READ_POST    = 'read.post';
    const EVENT_UPDATE_PRE   = 'update.pre';
    const EVENT_UPDATE_POST  = 'update.post';
    const EVENT_DELETE_PRE   = 'delete.pre';
    const EVENT_DELETE_POST  = 'delete.post';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * Set the request.
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->setParam('request', $request);
        $this->request = $request;
        return $this;
    }

    /**
     * Get the request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the response.
     *
     * @param Request $response
     */
    public function setResponse(Response $response)
    {
        $this->setParam('response', $response);
        $this->response = $response;
        return $this;
    }

    /**
     * Get the response.
     *
     * @return Responce
     */
    public function getResponse()
    {
        return $this->response;
    }
}

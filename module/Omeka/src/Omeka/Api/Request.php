<?php
namespace Omeka\Api;

use Omeka\Api\Exception;
use Zend\Stdlib\Request as ZendRequest;

/**
 * API request.
 */
class Request extends ZendRequest
{
    const SEARCH = 'search';
    const CREATE = 'create';
    const READ   = 'read';
    const UPDATE = 'update';
    const DELETE = 'delete';

    /**
     * @var array
     */
    protected $validOperations = array(
        self::SEARCH,
        self::CREATE,
        self::READ,
        self::UPDATE,
        self::DELETE,
    );

    /**
     * Construct an API request.
     * 
     * @param null|int $operation
     * @param null|string $resource
     */
    public function __construct($operation = null, $resource = null)
    {
        if (null !== $operation) {
            $this->setOperation($operation);
        }
        if (null !== $resource) {
            $this->setResource($resource);
        }
        // All requests are not sub-requests unless set otherwise.
        $this->setIsSubRequest(false);
    }

    /**
     * Set the request operation.
     * 
     * @param int $operation
     */
    public function setOperation($operation)
    {
        if (!in_array($operation, $this->validOperations)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The API does not support the "%s" operation.', 
                $operation
            ));
        }
        $this->setMetadata('operation', $operation);
    }

    /**
     * Get the request operation.
     * 
     * @return int
     */
    public function getOperation()
    {
        return $this->getMetadata('operation');
    }

    /**
     * Set the request resource.
     * 
     * @param string $resource
     */
    public function setResource($resource)
    {
        $this->setMetadata('resource', $resource);
    }

    /**
     * Get the request resource.
     * 
     * @return string
     */
    public function getResource()
    {
        return $this->getMetadata('resource');
    }

    /**
     * Set the request resource ID.
     * 
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->setMetadata('id', $id);
    }

    /**
     * Get the request resource ID.
     * 
     * @return mixed
     */
    public function getId()
    {
        return $this->getMetadata('id');
    }

    /**
     * Set whether this request is a sub-request.
     *
     * Sub-requests are requests that are executed within another API request.
     * This typically changes which operations are performed on the resource
     * during this request.
     * 
     * @param bool $isSubrequest
     */
    public function setIsSubRequest($isSubrequest)
    {
        $this->setMetadata('is_sub_request', (bool) $isSubrequest);
    }

    /**
     * Check whether this request is a sub-request.
     *
     * @return bool
     */
    public function isSubRequest()
    {
        return (bool) $this->getMetadata('is_sub_request');
    }
}

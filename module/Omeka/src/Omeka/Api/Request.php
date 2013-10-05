<?php
namespace Omeka\Api;

use Omeka\Api\Exception;

/**
 * API request.
 */
class Request
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
     * @var int
     */
    protected $operation;

    /**
     * @var string
     */
    protected $resource;

    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var mixed
     */
    protected $data;

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
        $this->operation = $operation;
    }

    /**
     * Get the request operation.
     * 
     * @return int
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Set the request resource.
     * 
     * @param string $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Get the request resource.
     * 
     * @return string
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set the request resource ID.
     * 
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the request resource ID.
     * 
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the request data.
     * 
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get the request data.
     * 
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}

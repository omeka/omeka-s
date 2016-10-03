<?php
namespace Omeka\Api;

use Zend\Stdlib\Request as ZendRequest;

/**
 * API request.
 */
class Request extends ZendRequest
{
    const SEARCH       = 'search';
    const CREATE       = 'create';
    const BATCH_CREATE = 'batch_create';
    const READ         = 'read';
    const UPDATE       = 'update';
    const DELETE       = 'delete';

    /**
     * @var array
     */
    protected $validOperations = [
        self::SEARCH,
        self::CREATE,
        self::BATCH_CREATE,
        self::READ,
        self::UPDATE,
        self::DELETE,
    ];

    /**
     * @var array
     */
    protected $content = [];

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
     * Check whether a request operation is valid.
     *
     * @return bool
     */
    public function isValidOperation($operation)
    {
        return in_array($operation, $this->validOperations);
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
     * Get a value from the content by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue($key, $default = null)
    {
        $data = $this->getContent();
        return (is_array($data) && array_key_exists($key, $data))
            ? $data[$key] : $default;
    }

    /**
     * Set the file data for the request.
     */
    public function setFileData($fileData)
    {
        $this->setMetadata('fileData', $fileData);
    }

    /**
     * Get the file data for the request.
     */
    public function getFileData()
    {
        return $this->getMetadata('fileData');
    }

    /**
     * Set whether this is a partial request (used for partial update, aka
     * PATCH)
     *
     * @param bool isPartial
     */
    public function setIsPartial($isPartial)
    {
        $this->setMetadata('isPartial', (bool) $isPartial);
    }

    /**
     * Whether this is a partial request.
     *
     * @return bool
     */
    public function isPartial()
    {
        return $this->getMetadata('isPartial', false);
    }

    /**
     * Set whether a batch operation should continue processing on error.
     *
     * @param bool $continueOnError
     */
    public function setContinueOnError($continueOnError)
    {
        $this->setMetadata('continueOnError', (bool) $continueOnError);
    }

    /**
     * Whether a batch operation should continue processing on error.
     */
    public function continueOnError()
    {
        return $this->getMetadata('continueOnError', false);
    }
}

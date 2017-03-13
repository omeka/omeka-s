<?php
namespace Omeka\Api;

/**
 * API request.
 */
class Request
{
    const SEARCH = 'search';
    const CREATE = 'create';
    const BATCH_CREATE = 'batch_create';
    const READ = 'read';
    const UPDATE = 'update';
    const DELETE = 'delete';

    /**
     * @var array
     */
    protected $validOperations = [
        self::SEARCH, self::CREATE, self::BATCH_CREATE,
        self::READ, self::UPDATE, self::DELETE,
    ];

    /**
     * @var string
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
     * @var array
     */
    protected $fileData = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $content = [];

    /**
     * Construct an API request.
     *
     * @param null|string $operation
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
     * @param string $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * Get the request operation.
     *
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
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
     * Set the file data for the request.
     *
     * @param array $fileData
     */
    public function setFileData(array $fileData)
    {
        $this->fileData = $fileData;
    }

    /**
     * Get the file data for the request.
     *
     * @return array
     */
    public function getFileData()
    {
        return $this->fileData;
    }

    /**
     * Set a request option or options.
     *
     * @param string|int|array $spec
     * @param mixed $value
     */
    public function setOption($spec, $value = null)
    {
        if (is_array($spec)) {
            foreach ($spec as $key => $value) {
                $this->options[$key] = $value;
            }
        } else {
            $this->options[$spec] = $value;
        }
    }

    /**
     * Get all options or a single option as specified by key.
     *
     * @param null|string|int $key
     * @param null|mixed $default
     * @return mixed
     */
    public function getOption($key = null, $default = null)
    {
        if (null === $key) {
            return $this->options;
        }
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }
        return $default;
    }

    /**
     * Set request content.
     *
     * The API request content must always be an array.
     *
     * @param array $value
     */
    public function setContent(array $value)
    {
        $this->content = $value;
    }

    /**
     * Get request content.
     *
     * @return array
     */
    public function getContent()
    {
        return $this->content;
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
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }
}

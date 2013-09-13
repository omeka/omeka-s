<?php
namespace Omeka\Api;

/**
 * An API request.
 */
class Request
{
    const FUNCTION_SEARCH = 'search';
    const FUNCTION_CREATE = 'create';
    const FUNCTION_READ   = 'read';
    const FUNCTION_UPDATE = 'update';
    const FUNCTION_DELETE = 'delete';
    
    /**
     * @var int
     */
    protected $function;
    
    /**
     * @var string
     */
    protected $resource;
    
    /**
     * Construct an API request.
     * 
     * @param null|int $function
     * @param null|string $resource
     */
    public function __construct($function = null, $resource = null)
    {
        $this->setFunction($function);
        $this->setResource($resource);
    }
    
    /**
     * Get the request function.
     * 
     * @return int
     */
    public function getFunction()
    {
        return $this->function;
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
     * Set the request function.
     * 
     * @param int $function
     */
    public function setFunction($function)
    {
        $validFunctions = array(
            self::FUNCTION_SEARCH,
            self::FUNCTION_CREATE,
            self::FUNCTION_READ,
            self::FUNCTION_UPDATE,
            self::FUNCTION_DELETE,
        );
        if (!in_array($function, $validFunctions)) {
            throw new \InvalidArgumentException(sprintf('The API does not support the "%s" function.', $function));
        }
        $this->function = $function;
    }
    
    /**
     * Set the requested resource.
     * 
     * @param string $resource
     */
    public function setResource($resource)
    {
        $this->resource = $resource;
    }
}

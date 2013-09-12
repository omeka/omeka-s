<?php
namespace Omeka\Api;

/**
 * An API request.
 */
class Request
{
    const FUNCTION_SEARCH = 1;
    const FUNCTION_CREATE = 2;
    const FUNCTION_READ   = 3;
    const FUNCTION_UPDATE = 4;
    const FUNCTION_DELETE = 5;
    
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
            throw new \InvalidArgumentException('The API request function is not supported.');
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

<?php
namespace Omeka\Api;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * API manager service.
 */
class Manager
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;
    
    /**
     * @var array Registered API resources and configuration.
     */
    protected $resources = array();
    
    /**
     * Execute an API request.
     * 
     * @param Request $request
     * @return Response
     */
    public function execute(Request $request)
    {
        // Validate the resource.
        if (!array_key_exists($request->getResource(), $this->getResources())) {
            throw new Exception\RuntimeException(sprintf('The "%s" resource is not registered.', $request->getResource()));
        }
        
        $resource = $this->getResources($request->getResource());
        
        // Validate the adapter class.
        if (!isset($resource['adapter_class'])) {
            throw new Exception\RuntimeException(sprintf('An adapter class is not registered for the "%s" resource.', $request->getResource()));
        }
        if (!class_exists($resource['adapter_class'])) {
            throw new Exception\RuntimeException(sprintf('The adapter class "%s" does not exist for the "%s" resource.', $resource['adapter_class'], $request->getResource()));
        }
        if (!in_array('Omeka\Api\Adapter\AdapterInterface', class_implements($resource['adapter_class']))) {
            throw new Exception\RuntimeException(sprintf('The adapter class "%s" does not implement Omeka\Api\Adapter\AdapterInterface for the "%s" resource.', $resource['adapter_class'], $request->getResource()));
        }
        
        // Validate the allowable functions.
        if (!isset($resource['functions'])) {
            throw new Exception\RuntimeException(sprintf('No functions are registered for the "%s" resource.', $request->getResource()));
        }
        if (!in_array($request->getFunction(), $resource['functions'])) {
            throw new Exception\RuntimeException(sprintf('The "%s" function is not implemented by the "%s" resource adapter.', $request->getFunction(), $request->getResource()));
        }
        
        $adapter = new $resource['adapter_class'];
        
        if (isset($resource['adapter_data'])) {
            $adapter->setData($resource['adapter_data']);
        }
        if ($adapter instanceof ServiceLocatorAwareInterface) {
            $adapter->setServiceLocator($this->getServiceLocator());
        }
        
        switch ($request->getFunction()) {
            case Request::FUNCTION_SEARCH:
                $response = $adapter->search();
                break;
            case Request::FUNCTION_CREATE:
                $response = $adapter->create();
                break;
        }
    }
    
    /**
     * Register an API resource.
     * 
     * @param string $name
     * @param array $config
     */
    public function registerResource($name, array $config)
    {
        $this->resources[$name] = $config;
    }
    
    /**
     * Register API resources.
     * 
     * @param array $resources
     */
    public function registerResources(array $resources)
    {
        foreach ($resources as $name => $config) {
            $this->registerResource($name, $config);
        }
    }
    
    /**
     * Get registered API resources.
     * 
     * @param null|string $name
     * @return array
     */
    public function getResources($name = null)
    {
        if (null === $name) {
            return $this->resources;
        }
        if (!array_key_exists($name, $this->resources)) {
            throw new Exception\RuntimeException(sprintf('The "%s" resource does not exist.', $name));
        }
        return $this->resources[$name];
    }
    
    /**
     * Set the service locator.
     * 
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }
    
    /**
     * Get the service locator.
     * 
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->services;
    }
}

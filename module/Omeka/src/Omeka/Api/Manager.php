<?php
namespace Omeka\Api;

use Omeka\Api\Exception as ApiException;
use Omeka\Api\Adapter\AdapterInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * API manager service.
 */
class Manager implements ServiceLocatorAwareInterface
{
    /**
     * @var array Registered API resources configuration.
     */
    protected $resourcesConfig = array();
    
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;
    
    /**
     * Respond to an API request.
     * 
     * @param Request $request
     * @return Response
     */
    public function respond(Request $request)
    {
        // Validate the resource.
        if (!array_key_exists($request->getResource(), $this->resourcesConfig)) {
            throw new ApiException(sprintf('The "%s" resource is not registered.', $request->getResource()));
        }
        
        $resourceConfig = $this->resourcesConfig[$request->getResource()];
        
        // Validate the adapter class.
        if (!isset($resourceConfig['adapter_class'])) {
            throw new ApiException(sprintf('An adapter class is not registered for the "%s" resource.', $request->getResource()));
        }
        if (!class_exists($resourceConfig['adapter_class'])) {
            throw new ApiException(sprintf('The adapter class "%s" does not exist for the "%s" resource.', $resourceConfig['adapter_class'], $request->getResource()));
        }
        if (!in_array('Omeka\Api\Adapter\AdapterInterface', class_implements($resourceConfig['adapter_class']))) {
            throw new ApiException(sprintf('The adapter class "%s" does not implement Omeka\Api\Adapter\AdapterInterface for the "%s" resource.', $resourceConfig['adapter_class'], $request->getResource()));
        }
        
        // Validate the allowable functions.
        if (!isset($resourceConfig['functions'])) {
            throw new ApiException(sprintf('No functions are registered for the "%s" resource.', $request->getResource()));
        }
        if (!in_array($request->getFunction(), $resourceConfig['functions'])) {
            throw new ApiException(sprintf('The "%s" function is not implemented by the "%s" resource adapter.', $request->getFunction(), $request->getResource()));
        }
        
        $adapter = new $resourceConfig['adapter_class'];
        
        if (isset($resourceConfig['adapter_data'])) {
            $adapter->setData($resourceConfig['adapter_data']);
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
     * @param array $data
     */
    public function registerResource($name, array $data)
    {
        $this->resourcesConfig[$name] = $data;
    }
    
    /**
     * Register API resources.
     * 
     * @param array $resources
     */
    public function registerResources(array $resources)
    {
        foreach ($resources as $name => $data) {
            $this->registerResource($name, $data);
        }
    }
    
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
    }
    
    public function getServiceLocator()
    {
        return $this->services;
    }
}

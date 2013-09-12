<?php
namespace Omeka\Api;

use Omeka\Api\Exception as ApiException;

/**
 * The API manager service.
 */
class Manager
{
    /**
     * @var array Registered API resources.
     */
    protected $resources = array();
    
    /**
     * Respond to an API request.
     * 
     * @param Request $request
     * @return Response
     */
    public function respond(Request $request)
    {
        // Set the resource.
        if (!array_key_exists($request->getResource(), $this->resources)) {
            throw new ApiException(sprintf('The "%s" resource is not registered.', $request->getResource()));
        }
        $resourceConfig = $this->resources[$request->getResource()];
        
        // Set the adapter.
        if (!isset($resourceConfig['adapter_class'])) {
            throw new ApiException(sprintf('An adapter class is not registered for the "%s" resource.', $request->getResource()));
        }
        if (!isset($resourceConfig['functions'])) {
            throw new ApiException(sprintf('No functions are registered for the "%s" resource.', $request->getResource()));
        }
        if (!in_array($request->getFunction(), $resourceConfig['functions'])) {
            throw new ApiException(sprintf('The requested function is not implemented for the "%s" resource.', $request->getResource()));
        }
        if (!isset($resourceConfig['adapter_data'])) {
            $resource['adapter_data'] = array();
        }
        $adapter = new $resourceConfig['adapter_class']($resourceConfig['adapter_data']);
        
        // call adapter to do the actual work
    }
    
    /**
     * Register an API resource.
     * 
     * @param string $name
     * @param array $data
     */
    public function registerResource($name, array $data)
    {
        $this->resources[$name] = $data;
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
}

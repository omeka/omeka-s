<?php
namespace Omeka\Api;

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
        // GET A RESPONSE TO THE API REQUEST
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

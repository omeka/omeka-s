<?php
namespace Omeka\Api;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Exception;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * API manager service.
 */
class Manager implements ServiceLocatorAwareInterface
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
     * @param AdapterInterface $adapter
     * @return Response
     */
    public function execute(Request $request, AdapterInterface $adapter = null)
    {
        if (null === $adapter) {
            $adapter = $this->getAdapter($request->getResource());
        }
        switch ($request->getOperation()) {
            case Request::SEARCH:
                $response = $adapter->search();
                break;
            case Request::CREATE:
                $response = $adapter->create();
                break;
            case Request::READ:
                $response = $adapter->read();
                break;
            case Request::UPDATE:
                $response = $adapter->update();
                break;
            case Request::DELETE:
                $response = $adapter->delete();
                break;
            default:
                throw new Exception\InvalidRequestException(sprintf(
                    'The "%s" operation is not implemented by the "%s" resource adapter.', 
                    $request->getOperation(), 
                    $request->getResource()
                ));
        }
        return new Response($response, $request);
    }

    /**
     * Get the API adapter.
     * 
     * @param string $resource
     * @return AdapterInterface
     */
    public function getAdapter($resource)
    {
        $config = $this->getResource($resource);
        $adapter = new $config['adapter_class'];
        if (isset($config['adapter_data'])) {
            $adapter->setData($config['adapter_data']);
        }
        if ($adapter instanceof ServiceLocatorAwareInterface) {
            $adapter->setServiceLocator($this->getServiceLocator());
        }
        return $adapter;
    }

    /**
     * Register an API resource.
     * 
     * @param string $resource
     * @param array $config
     */
    public function registerResource($resource, array $config)
    {
        if (!isset($config['adapter_class'])) {
            throw new Exception\ConfigException(sprintf(
                'An adapter class is not registered for the "%s" resource.', 
                $resource
            ));
        }
        if (!class_exists($config['adapter_class'])) {
            throw new Exception\ConfigException(sprintf(
                'The adapter class "%s" does not exist for the "%s" resource.', 
                $config['adapter_class'], 
                $resource
            ));
        }
        if (!in_array('Omeka\Api\Adapter\AdapterInterface', class_implements($config['adapter_class']))) {
            throw new Exception\ConfigException(sprintf(
                'The adapter class "%s" does not implement Omeka\Api\Adapter\AdapterInterface for the "%s" resource.', 
                $config['adapter_class'], 
                $resource
            ));
        }
        if (empty($config['operations'])) {
            throw new Exception\ConfigException(sprintf(
                'No operations are registered for the "%s" resource.', 
                $resource
            ));
        }
        $this->resources[$resource] = $config;
    }

    /**
     * Register API resources.
     * 
     * @param array $resources
     */
    public function registerResources(array $resources)
    {
        foreach ($resources as $resource => $config) {
            $this->registerResource($resource, $config);
        }
    }

    /**
     * Get registered API resources.
     * 
     * @return array
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Get registered API resource.
     * 
     * @param string $resource
     * @return array
     */
    public function getResource($resource)
    {
        if (!$this->isRegistered($resource)) {
            throw new Exception\InvalidRequestException(sprintf(
                'The "%s" resource is not registered.', 
                $resource
            ));
        }
        return $this->resources[$resource];
    }

    /**
     * Check that resource is registered.
     * 
     * @param string $resource
     * @return bool
     */
    public function isRegistered($resource)
    {
        if (!array_key_exists($resource, $this->resources)) {
            return false;
        }
        return true;
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

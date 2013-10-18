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
     * @param null|AdapterInterface $adapter
     * @return Response
     */
    public function execute(Request $request, AdapterInterface $adapter = null)
    {
        try {
            $response = $this->getResponse($request, $adapter);
        } catch (\Exception $e) {
            $this->getServiceLocator()->get('Logger')->err($e->__toString());
            // Always return a Response object, regardless of exception.
            $response = new Response;
            $response->setStatus(Response::ERROR_INTERNAL);
            $response->addError(Response::ERROR_INTERNAL, $e->getMessage());
        }
        $response->setRequest($request);
        return $response;
    }

    /**
     * Get the response of an API request.
     * 
     * @param Request $request
     * @param null|AdapterInterface $adapter
     * @return Response
     */
    protected function getResponse(Request $request, $adapter)
    {
        if (!$this->resourceIsRegistered($request->getResource())) {
            throw new Exception\InvalidRequestException(sprintf(
                'The "%s" resource is not registered.', 
                $request->getResource()
            ));
        }
        if (null === $adapter) {
            $adapter = $this->getAdapter($request->getResource());
        }
        switch ($request->getOperation()) {
            case Request::SEARCH:
                $response = $adapter->search($request->getContent());
                break;
            case Request::CREATE:
                $response = $adapter->create($request->getContent());
                break;
            case Request::READ:
                $response = $adapter->read($request->getId(), $request->getContent());
                break;
            case Request::UPDATE:
                $response = $adapter->update($request->getId(), $request->getContent());
                break;
            case Request::DELETE:
                $response = $adapter->delete($request->getId(), $request->getContent());
                break;
            default:
                throw new Exception\InvalidRequestException(sprintf(
                    'The API does not support the "%s" operation.',
                    $request->getOperation()
                ));
        }
        if (!$response instanceof Response) {
            throw new Exception\InvalidResponseException(sprintf(
                'The "%s" operation for the "%s" resource adapter did not return an Omeka\Api\Response object.',
                $request->getOperation(),
                $request->getResource()
            ));
        }
        return $response;
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
        if (!$this->resourceIsRegistered($resource)) {
            throw new Exception\InvalidRequestException(sprintf(
                'The "%s" resource is not registered.', 
                $resource
            ));
        }
        return $this->resources[$resource];
    }

    /**
     * Check that a resource is registered.
     * 
     * @param string $resource
     * @return bool
     */
    public function resourceIsRegistered($resource)
    {
        return array_key_exists($resource, $this->resources);
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

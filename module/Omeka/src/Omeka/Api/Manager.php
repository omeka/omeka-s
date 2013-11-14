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
     * Execute a search API request.
     *
     * @param string $resource
     * @param mixed $data
     * @return Response
     */
    public function search($resource, $data = null)
    {
        $request = new Request(Request::SEARCH, $resource);
        $request->setContent($data);
        return $this->execute($request);
    }

    /**
     * Execute a create API request.
     *
     * @param string $resource
     * @param mixed $data
     * @return Response
     */
    public function create($resource, $data = null)
    {
        $request = new Request(Request::CREATE, $resource);
        $request->setContent($data);
        return $this->execute($request);
    }

    /**
     * Execute a read API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param mixed $data
     * @return Response
     */
    public function read($resource, $id, $data = null)
    {
        $request = new Request(Request::READ, $resource);
        $request->setId($id);
        $request->setContent($data);
        return $this->execute($request);
    }

    /**
     * Execute an update API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param mixed $data
     * @return Response
     */
    public function update($resource, $id, $data = null)
    {
        $request = new Request(Request::UPDATE, $resource);
        $request->setId($id);
        $request->setContent($data);
        return $this->execute($request);
    }

    /**
     * Execute a delete API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param mixed $data
     * @return Response
     */
    public function delete($resource, $id, $data = null)
    {
        $request = new Request(Request::DELETE, $resource);
        $request->setId($id);
        $request->setContent($data);
        return $this->execute($request);
    }

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
        } catch (Exception\BadRequestException $e) {
            $response = new Response;
            $response->setStatus(Response::ERROR_BAD_REQUEST);
            $response->addError(Response::ERROR_BAD_REQUEST, $e->getMessage());
        } catch (Exception\BadResponseException $e) {
            $response = new Response;
            $response->setStatus(Response::ERROR_BAD_RESPONSE);
            $response->addError(Response::ERROR_BAD_RESPONSE, $e->getMessage());
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
            throw new Exception\BadRequestException(sprintf(
                'The "%s" resource is not registered.', 
                $request->getResource()
            ));
        }
        if (null === $adapter) {
            $adapter = $this->getAdapter($request);
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
                throw new Exception\BadRequestException(sprintf(
                    'The API does not support the "%s" operation.',
                    $request->getOperation()
                ));
        }
        if (!$response instanceof Response) {
            throw new Exception\BadResponseException(sprintf(
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
     * Note that this sets the Request and ServiceLocator objects to the adapter
     * if it implements their respective interfaces.
     * 
     * @param Request $request
     * @return AdapterInterface
     */
    public function getAdapter(Request $request)
    {
        $config = $this->getResource($request->getResource());
        $adapter = new $config['adapter_class'];
        if ($adapter instanceof RequestAwareInterface) {
            $adapter->setRequest($request);
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
            throw new Exception\BadRequestException(sprintf(
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

<?php
namespace Omeka\Api;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Exception;
use Omeka\Event\ApiEvent;
use Omeka\Stdlib\ClassCheck;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * API manager service.
 */
class Manager implements ServiceLocatorAwareInterface, EventManagerAwareInterface
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var EventManagerInterface
     */
    protected $events;

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
     * Execute a batch create API request.
     *
     * @param string $resource
     * @param mixed $data
     * @return Response
     */
    public function batchCreate($resource, $data = null)
    {
        $request = new Request(Request::BATCH_CREATE, $resource);
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
     * @param null|AdapterInterface $adapter Custom adapter
     * @return Response
     */
    public function execute(Request $request, AdapterInterface $adapter = null)
    {
        try {
            if (!$this->resourceIsRegistered($request->getResource())) {
                throw new Exception\BadRequestException(sprintf(
                    'The "%s" resource is not registered.', 
                    $request->getResource()
                ));
            }

            if (null === $adapter) {
                // Use the registered adapter if a custom one is not passed.
                $config = $this->getResource($request->getResource());
                $adapter = new $config['adapter_class'];
            }

            // Set adapter dependencies.
            $adapter->setRequest($request);
            if ($adapter instanceof ServiceLocatorAwareInterface) {
                $adapter->setServiceLocator($this->getServiceLocator());
            }
            if ($adapter instanceof EventManagerAwareInterface) {
                $adapter->setEventManager(
                    $this->getServiceLocator()->get('EventManager')
                );
            }

            // Verify that the current user has general access to this resource.
            $acl = $this->getServiceLocator()->get('Acl');
            $isAllowed = $acl->isAllowed(
                'current-user',
                $adapter,
                $request->getOperation()
            );
            if (!$isAllowed) {
                throw new Exception\PermissionDeniedException(sprintf(
                    'Permission denied for the current user to %s the %s resource.',
                    $request->getOperation(),
                    $adapter->getResourceId()
                ));
            }

            // Trigger the execute.pre event.
            $event = new ApiEvent;
            $event->setTarget($this)->setRequest($request);
            $this->getEventManager()->trigger(ApiEvent::EVENT_EXECUTE_PRE, $event);

            if ($adapter instanceof EventManagerAwareInterface) {
                // Trigger the operation.pre event.
                $event = new ApiEvent;
                $event->setTarget($adapter)->setRequest($request);
                $adapter->getEventManager()
                    ->trigger($request->getOperation() . '.pre', $event);
            }

            switch ($request->getOperation()) {
                case Request::SEARCH:
                    $response = $adapter->search($request->getContent());
                    break;
                case Request::CREATE:
                    $response = $adapter->create($request->getContent());
                    break;
                case Request::BATCH_CREATE:
                    $response = $this->executeBatchCreate($request, $adapter);
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

            if ($adapter instanceof EventManagerAwareInterface) {
                // Trigger the operation.post event.
                $event = new ApiEvent;
                $event->setTarget($adapter)->setRequest($request)
                    ->setResponse($response);
                $adapter->getEventManager()
                    ->trigger($request->getOperation() . '.post', $event);
            }

        // Always return a Response object, regardless of exception.
        } catch (Exception\BadRequestException $e) {
            $this->getServiceLocator()->get('Logger')->err((string) $e);
            $response = new Response;
            $response->setStatus(Response::ERROR_BAD_REQUEST);
            $response->addError(Response::ERROR_BAD_REQUEST, $e->getMessage());
        } catch (Exception\BadResponseException $e) {
            $this->getServiceLocator()->get('Logger')->err((string) $e);
            $response = new Response;
            $response->setStatus(Response::ERROR_BAD_RESPONSE);
            $response->addError(Response::ERROR_BAD_RESPONSE, $e->getMessage());
        } catch (Exception\PermissionDeniedException $e) {
            $this->getServiceLocator()->get('Logger')->err((string) $e);
            $response = new Response;
            $response->setStatus(Response::ERROR_PERMISSION_DENIED);
            $response->addError(Response::ERROR_PERMISSION_DENIED, $e->getMessage());
        } catch (\Exception $e) {
            $this->getServiceLocator()->get('Logger')->err((string) $e);
            $response = new Response;
            $response->setStatus(Response::ERROR_INTERNAL);
            $response->addError(Response::ERROR_INTERNAL, $e->getMessage());
        }

        // Trigger the execute.post event.
        $event = new ApiEvent;
        $event->setTarget($this)->setRequest($request)->setResponse($response);
        $this->getEventManager()->trigger(ApiEvent::EVENT_EXECUTE_POST, $event);

        $response->setRequest($request);
        return $response;
    }

    /**
     * Execute a batch create operation.
     *
     * @param Request $request
     * @param null|AdapterInterface $adapter Custom adapter
     * @return Response
     */
    protected function executeBatchCreate(Request $request, AdapterInterface $adapter)
    {
        if (!is_array($request->getContent())) {
            throw new Exception\BadRequestException('Invalid batch operation request data.');
        }

        // Create a simulated request for individual create events.
        $createRequest = new Request(
            Request::CREATE,
            $request->getResource()
        );

        // Trigger the create.pre event for every resource.
        foreach ($request->getContent() as $content) {
            $createRequest->setContent($content);
            $createEvent = new ApiEvent;
            $createEvent->setTarget($adapter)->setRequest($createRequest);
            $adapter->getEventManager()->trigger(
                Request::CREATE . '.pre',
                $createEvent
            );
        }

        $response = $adapter->batchCreate($request->getContent());

        // Do not trigger create.post events if an error has occured or if the
        // response does not return valid content.
        if ($response->isError() || !is_array($response->getContent())) {
            return $response;
        }

        // Trigger the create.post event for every created resource.
        foreach ($response->getContent() as $resource) {
            $createRequest->setContent($resource);
            $createEvent = new ApiEvent;
            $createEvent->setTarget($adapter)->setRequest($createRequest)
                ->setResponse(new Response($resource));
            $adapter->getEventManager()->trigger(
                Request::CREATE . '.post',
                $createEvent
            );
        }

        return $response;
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
        if (!ClassCheck::isInterfaceOf(
            'Omeka\Api\Adapter\AdapterInterface',
            $config['adapter_class'])
        ) {
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

    /**
     * Set the event manager.
     *
     * @param EventManagerInterface $events
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(get_class($this));
        $this->events = $events;
    }

    /**
     * Get the event manager.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        return $this->events;
    }
}

<?php
namespace Omeka\Api;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Exception;
use Omeka\Event\Event;
use Omeka\Stdlib\ClassCheck;
use Zend\EventManager\EventManagerAwareInterface;
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
     * @var array Registered API resources and their adapters.
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
     * @return Response
     */
    public function execute(Request $request)
    {
        try {
            // Set the adapter and its dependencies.
            $adapterClass = $this->getAdapterClass($request->getResource());
            $adapter = new $adapterClass;
            $adapter->setRequest($request);
            $adapter->setServiceLocator($this->getServiceLocator());

            // Verify that the current user has general access to this resource.
            $acl = $this->getServiceLocator()->get('Omeka\Acl');
            if (!$acl->isAllowed('current_user', $adapter, $request->getOperation())) {
                throw new Exception\PermissionDeniedException(sprintf(
                    'Permission denied for the current user to %s the %s resource.',
                    $request->getOperation(),
                    $adapter->getResourceId()
                ));
            }

            // Trigger the execute.pre event.
            $event = new Event(Event::EVENT_EXECUTE_PRE, $adapter, array(
                'services' => $this->getServiceLocator(),
                'request' => $request,
            ));
            $adapter->getEventManager()->trigger($event);

            // Trigger the {operation}.pre event.
            $event = new Event($request->getOperation() . '.pre', $adapter, array(
                'services' => $this->getServiceLocator(),
                'request' => $request,
            ));
            $adapter->getEventManager()->trigger($event);

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

            // Trigger the {operation}.post event.
            $event = new Event($request->getOperation() . '.post', $adapter, array(
                'services' => $this->getServiceLocator(),
                'request' => $request,
                'response' => $response,
            ));
            $adapter->getEventManager()->trigger($event);

            // Trigger the execute.post event.
            $event = new Event(Event::EVENT_EXECUTE_POST, $adapter, array(
                'services' => $this->getServiceLocator(),
                'request' => $request,
                'response' => $response,
            ));
            $adapter->getEventManager()->trigger($event);

        // Always return a Response object, regardless of exception.
        } catch (Exception\BadRequestException $e) {
            $this->getServiceLocator()->get('Omeka\Logger')->err((string) $e);
            $response = new Response;
            $response->setStatus(Response::ERROR_BAD_REQUEST);
            $response->addError(Response::ERROR_BAD_REQUEST, $e->getMessage());
        } catch (Exception\BadResponseException $e) {
            $this->getServiceLocator()->get('Omeka\Logger')->err((string) $e);
            $response = new Response;
            $response->setStatus(Response::ERROR_BAD_RESPONSE);
            $response->addError(Response::ERROR_BAD_RESPONSE, $e->getMessage());
        } catch (Exception\PermissionDeniedException $e) {
            $this->getServiceLocator()->get('Omeka\Logger')->err((string) $e);
            $response = new Response;
            $response->setStatus(Response::ERROR_PERMISSION_DENIED);
            $response->addError(Response::ERROR_PERMISSION_DENIED, $e->getMessage());
        } catch (\Exception $e) {
            $this->getServiceLocator()->get('Omeka\Logger')->err((string) $e);
            $response = new Response;
            $response->setStatus(Response::ERROR_INTERNAL);
            $response->addError(Response::ERROR_INTERNAL, $e->getMessage());
        }

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
            $createEvent = new Event(Event::EVENT_CREATE_PRE, $adapter, array(
                'request' => $createRequest,
            ));
            $adapter->getEventManager()->trigger($createEvent);
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
            $createEvent = new Event(Event::EVENT_CREATE_POST, $adapter, array(
                'request' => $createRequest,
                'response' => new Response($resource),
            ));
            $adapter->getEventManager()->trigger($createEvent);
        }

        return $response;
    }

    /**
     * Register an API resource.
     *
     * All API adapters must implement the following interfaces:
     *
     * - Omeka\Api\Adapter\AdapterInterface
     * - Zend\ServiceManager\ServiceLocatorAwareInterface
     * - Zend\EventManager\EventManagerAwareInterface
     * - Zend\Permissions\Acl\Resource\ResourceInterface
     * 
     * @param string $resource
     * @param string $adapterClass
     */
    public function registerResource($resource, $adapterClass)
    {
        if (!class_exists($adapterClass)) {
            throw new Exception\ConfigException(sprintf(
                'The adapter class "%s" does not exist for the "%s" resource.', 
                $adapterClass,
                $resource
            ));
        }
        $requiredInterfaces = array(
            'Omeka\Api\Adapter\AdapterInterface',
            'Zend\ServiceManager\ServiceLocatorAwareInterface',
            'Zend\EventManager\EventManagerAwareInterface',
            'Zend\Permissions\Acl\Resource\ResourceInterface',
        );
        foreach ($requiredInterfaces as $requiredInterface) {
            if (!is_subclass_of($adapterClass, $requiredInterface)) {
                throw new Exception\ConfigException(sprintf(
                    'The adapter class "%s" does not implement %s for the "%s" resource.', 
                    $adapterClass, $requiredInterface, $resource
                ));
            }
        }
        $this->resources[$resource] = $adapterClass;
    }

    /**
     * Register API resources.
     * 
     * @param array $resources
     */
    public function registerResources(array $resources)
    {
        foreach ($resources as $resource => $adapterClass) {
            $this->registerResource($resource, $adapterClass);
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
     * Get registered API resource adapter class.
     * 
     * @param string $resource
     * @return string
     */
    public function getAdapterClass($resource)
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

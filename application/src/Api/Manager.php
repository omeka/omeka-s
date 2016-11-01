<?php
namespace Omeka\Api;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Adapter\Manager as AdapterManager;
use Omeka\Api\Representation\RepresentationInterface;
use Omeka\Permissions\Acl;
use Zend\Log\LoggerInterface;
use Zend\I18n\Translator\TranslatorInterface;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\EventManager\Event;

/**
 * API manager service.
 */
class Manager
{
    /**
     * @var AdapterManager
     */
    protected $adapterManager;

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(AdapterManager $adapterManager, Acl $acl, LoggerInterface $logger,
        TranslatorInterface $translator)
    {
        $this->adapterManager = $adapterManager;
        $this->acl = $acl;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * Execute a search API request.
     *
     * @param string $resource
     * @param array $data
     * @return Response
     */
    public function search($resource, $data = [])
    {
        $request = new Request(Request::SEARCH, $resource);
        $request->setContent($data);
        return $this->execute($request);
    }

    /**
     * Execute a create API request.
     *
     * @param string $resource
     * @param array $data
     * @param array $fileData
     * @return Response
     */
    public function create($resource, $data = [], $fileData = [])
    {
        $request = new Request(Request::CREATE, $resource);
        $request->setContent($data);
        $request->setFileData($fileData);
        return $this->execute($request);
    }

    /**
     * Execute a batch create API request.
     *
     * @param string $resource
     * @param array $data
     * @param array $fileData
     * @param bool $continueOnError
     * @return Response
     */
    public function batchCreate($resource, $data = [], $fileData = [],
        $continueOnError = false
    ) {
        $request = new Request(Request::BATCH_CREATE, $resource);
        $request->setContent($data);
        $request->setContinueOnError($continueOnError);
        return $this->execute($request);
    }

    /**
     * Execute a read API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @return Response
     */
    public function read($resource, $id, $data = [])
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
     * @param array $data
     * @param array $fileData
     * @param bool $partial
     * @return Response
     */
    public function update($resource, $id, $data = [], $fileData = [],
        $partial = false
    ) {
        $request = new Request(Request::UPDATE, $resource);
        $request->setId($id);
        $request->setContent($data);
        $request->setFileData($fileData);
        $request->setIsPartial($partial);
        return $this->execute($request);
    }

    /**
     * Execute a delete API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @return Response
     */
    public function delete($resource, $id, $data = [])
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
            $t = $this->translator;

            // Validate the request.
            if (null === $request->getResource() || '' === $request->getResource()) {
                throw new Exception\BadRequestException($t->translate('The API request must include a resource. None given'));
            }
            if (!$request->isValidOperation($request->getOperation())) {
                throw new Exception\BadRequestException(sprintf(
                    $t->translate('The API does not support the "%1$s" request operation.'),
                    $request->getOperation()
                ));
            }
            if (!is_array($request->getContent())) {
                throw new Exception\BadRequestException(sprintf(
                    $t->translate('The API request content must be a JSON object (for HTTP) or PHP array. "%1$s" given.'),
                    gettype($request->getContent())
                ));
            }

            // Get the adapter.
            try {
                $adapter = $this->adapterManager->get($request->getResource());
            } catch (ServiceNotFoundException $e) {
                throw new Exception\BadRequestException(sprintf(
                    $t->translate('The API does not support the "%1$s" resource.'),
                    $request->getResource()
                ));
            }

            // Verify that the current user has general access to this resource.
            if (!$this->acl->userIsAllowed($adapter, $request->getOperation())) {
                throw new Exception\PermissionDeniedException(sprintf(
                    $t->translate('Permission denied for the current user to %1$s the %2$s resource.'),
                    $request->getOperation(),
                    $adapter->getResourceId()
                ));
            }

            // Trigger the api.execute.pre event.
            $event = new Event('api.execute.pre', $adapter, [
                'request' => $request,
            ]);
            $adapter->getEventManager()->triggerEvent($event);

            // Trigger the api.{operation}.pre event.
            $event = new Event(
                'api.' . $request->getOperation() . '.pre',
                $adapter, [
                    'request' => $request,
                ]
            );
            $adapter->getEventManager()->triggerEvent($event);

            switch ($request->getOperation()) {
                case Request::SEARCH:
                    $response = $adapter->search($request);
                    break;
                case Request::CREATE:
                    $response = $adapter->create($request);
                    break;
                case Request::BATCH_CREATE:
                    $response = $this->executeBatchCreate($request, $adapter);
                    break;
                case Request::READ:
                    $response = $adapter->read($request);
                    break;
                case Request::UPDATE:
                    $response = $adapter->update($request);
                    break;
                case Request::DELETE:
                    $response = $adapter->delete($request);
                    break;
                default:
                    throw new Exception\BadRequestException(sprintf(
                        $t->translate('The API does not support the "%1$s" request operation.'),
                        $request->getOperation()
                    ));
            }

            // Validate the response.
            if (!$response instanceof Response) {
                throw new Exception\BadResponseException(sprintf(
                    $t->translate('The "%1$s" operation for the "%2$s" adapter did not return a valid response.'),
                    $request->getOperation(),
                    $request->getResource()
                ));
            }
            if (!$response->isValidStatus($response->getStatus())) {
                throw new Exception\BadResponseException(sprintf(
                    $t->translate('The "%1$s" operation for the "%2$s" adapter did not return a valid response status.'),
                    $request->getOperation(),
                    $request->getResource()
                ));
            }
            if (!$this->isValidResponseContent($response)) {
                throw new Exception\BadResponseException(sprintf(
                    $t->translate('The "%1$s" operation for the "%2$s" adapter did not return valid response content.'),
                    $request->getOperation(),
                    $request->getResource()
                ));
            }

            // Trigger the api.{operation}.post event.
            $event = new Event(
                'api.' . $request->getOperation() . '.post',
                $adapter,
                [
                    'request' => $request,
                    'response' => $response,
                ]
            );
            $adapter->getEventManager()->triggerEvent($event);

            // Trigger the api.execute.post event.
            $event = new Event('api.execute.post', $adapter, [
                'request' => $request,
                'response' => $response,
            ]);
            $adapter->getEventManager()->triggerEvent($event);
        } catch (Exception\ValidationException $e) {
            $this->logger->err((string) $e);
            $response = new Response;
            $response->setStatus(Response::ERROR_VALIDATION);
            $response->mergeErrors($e->getErrorStore());
        }

        $response->setRequest($request);
        return $response;
    }

    /**
     * Check whether the response content is valid.
     *
     * A valid response content is a representation object or an array
     * containing representation objects.
     *
     * @param Response $response
     * @return bool
     */
    protected function isValidResponseContent(Response $response)
    {
        $content = $response->getContent();
        if ($content instanceof RepresentationInterface) {
            return true;
        }
        if (is_array($content)) {
            foreach ($content as $representation) {
                if (!$representation instanceof RepresentationInterface) {
                    return false;
                }
            }
            return true;
        }
        return false;
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
        $t = $this->translator;
        if (!is_array($request->getContent())) {
            throw new Exception\BadRequestException(
                $t->translate('Invalid batch operation request data.')
            );
        }

        // Create a simulated request for individual create events.
        $createRequest = new Request(
            Request::CREATE,
            $request->getResource()
        );

        // Trigger the create.pre event for every resource.
        foreach ($request->getContent() as $content) {
            $createRequest->setContent($content);
            $createEvent = new Event('api.create.pre', $adapter, [
                'request' => $createRequest,
            ]);
            $adapter->getEventManager()->triggerEvent($createEvent);
        }

        $response = $adapter->batchCreate($request);

        // Do not trigger create.post events if an error has occured or if the
        // response does not return valid content.
        if ($response->isError() || !is_array($response->getContent())) {
            return $response;
        }

        // Trigger the create.post event for every created resource.
        foreach ($response->getContent() as $resource) {
            $createRequest->setContent($resource);
            $createEvent = new Event('api.create.post', $adapter, [
                'request' => $createRequest,
                'response' => new Response($resource),
            ]);
            $adapter->getEventManager()->triggerEvent($createEvent);
        }

        return $response;
    }
}

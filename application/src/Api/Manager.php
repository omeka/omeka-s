<?php
namespace Omeka\Api;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Adapter\Manager as AdapterManager;
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
     * @param array $options
     * @return Response
     */
    public function search($resource, array $data = [], array $options = [])
    {
        $request = new Request(Request::SEARCH, $resource);
        $request->setContent($data)
            ->setOption($options);
        return $this->execute($request);
    }

    /**
     * Execute a create API request.
     *
     * @param string $resource
     * @param array $data
     * @param array $fileData
     * @param array $options
     * @return Response
     */
    public function create($resource, array $data = [], $fileData = [],
        array $options = []
    ) {
        $request = new Request(Request::CREATE, $resource);
        $request->setContent($data)
            ->setFileData($fileData)
            ->setOption($options);
        return $this->execute($request);
    }

    /**
     * Execute a batch create API request.
     *
     * @param string $resource
     * @param array $data
     * @param array $fileData
     * @param array $options
     * @return Response
     */
    public function batchCreate($resource, array $data = [], $fileData = [],
        array $options = []
    ) {
        $request = new Request(Request::BATCH_CREATE, $resource);
        $request->setContent($data)
            ->setFileData($fileData)
            ->setOption($options);
        return $this->execute($request);
    }

    /**
     * Execute a read API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @param array $options
     * @return Response
     */
    public function read($resource, $id, array $data = [], array $options = [])
    {
        $request = new Request(Request::READ, $resource);
        $request->setId($id)
            ->setContent($data)
            ->setOption($options);
        return $this->execute($request);
    }

    /**
     * Execute an update API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @param array $fileData
     * @param array $options
     * @return Response
     */
    public function update($resource, $id, array $data = [], array $fileData = [],
        array $options = []
    ) {
        $request = new Request(Request::UPDATE, $resource);
        $request->setId($id)
            ->setContent($data)
            ->setFileData($fileData)
            ->setOption($options);
        return $this->execute($request);
    }

    /**
     * Execute a delete API request.
     *
     * @param string $resource
     * @param mixed $id
     * @param array $data
     * @param array $options
     * @return Response
     */
    public function delete($resource, $id, array $data = [], array $options = [])
    {
        $request = new Request(Request::DELETE, $resource);
        $request->setId($id)
            ->setContent($data)
            ->setOption($options);
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
        $t = $this->translator;

        // Get the adapter.
        try {
            $adapter = $this->adapterManager->get($request->getResource());
        } catch (ServiceNotFoundException $e) {
            throw new Exception\BadRequestException(sprintf(
                $t->translate('The API does not support the "%s" resource.'),
                $request->getResource()
            ));
        }

        // Verify that the current user has general access to this resource.
        if (!$this->acl->userIsAllowed($adapter, $request->getOperation())) {
            throw new Exception\PermissionDeniedException(sprintf(
                $t->translate('Permission denied for the current user to %s the %s resource.'),
                $request->getOperation(),
                $adapter->getResourceId()
            ));
        }

        if ($request->getOption('initialize', true)) {
            $this->initialize($adapter, $request);
        }

        switch ($request->getOperation()) {
            case Request::SEARCH:
                $response = $adapter->search($request);
                break;
            case Request::CREATE:
                $response = $adapter->create($request);
                break;
            case Request::BATCH_CREATE:
                $response = $adapter->batchCreate($request);
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
                    $t->translate('The API does not support the "%s" request operation.'),
                    $request->getOperation()
                ));
        }

        // Validate the response.
        if (!$response instanceof Response) {
            throw new Exception\BadResponseException(sprintf(
                $t->translate('The "%s" operation for the "%s" adapter did not return a valid response.'),
                $request->getOperation(),
                $request->getResource()
            ));
        }

        if ($request->getOption('finalize', true)) {
            $this->finalize($adapter, $request, $response);
        }

        $response->setRequest($request);
        return $response;
    }

    /**
     * Initialize the request.
     *
     * Triggers the API-pre events.
     *
     * @param AdapterInterface $adapter
     * @param Request $request
     */
    public function initialize(AdapterInterface $adapter, Request $request)
    {
        $eventManager = $adapter->getEventManager();

        $event = new Event(
            'api.execute.pre',
            $adapter,
            ['request' => $request]
        );
        $eventManager->triggerEvent($event);

        // Trigger the api.{operation}.pre event.
        $event = new Event(
            sprintf('api.%s.pre', $request->getOperation()),
            $adapter,
            ['request' => $request]
        );
        $eventManager->triggerEvent($event);
    }

    /**
     * Finalize the request.
     *
     * Triggers the API-post events.
     *
     * @param AdapterInterface $adapter
     * @param Request $request
     * @param Response $response
     */
    public function finalize(AdapterInterface $adapter, Request $request,
        Response $response
    ) {
        $eventManager = $adapter->getEventManager();

        $event = new Event(
            sprintf('api.%s.post', $request->getOperation()),
            $adapter,
            ['request' => $request, 'response' => $response]
        );
        $eventManager->triggerEvent($event);

        $event = new Event(
            'api.execute.post',
            $adapter,
            [
                'request' => $request,
                'response' => $response,
            ]
        );
        $eventManager->triggerEvent($event);
    }
}

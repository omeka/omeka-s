<?php
namespace Omeka\Controller;

use Omeka\Api\Manager as ApiManager;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\Paginator;
use Omeka\View\Model\ApiJsonModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\RequestInterface as Request;

class ApiController extends AbstractRestfulController
{
    /**
     * @var Paginator
     */
    protected $paginator;

    /**
     * @var array
     */
    protected $viewOptions = [];

    /**
     * @var ApiManager
     */
    protected $api;

    /**
     * @param Paginator $paginator
     */
    public function __construct(Paginator $paginator, ApiManager $api)
    {
        $this->paginator = $paginator;
        $this->api = $api;
    }

    /**
     * Fetch all contexts and render a JSON-LD context object.
     */
    public function contextAction()
    {
        $eventManager = $this->getEventManager();
        $args = $eventManager->prepareArgs(['context' => []]);
        $eventManager->triggerEvent(new MvcEvent('api.context', null, $args));
        return new ApiJsonModel(['@context' => $args['context']], $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api->read($resource, $id);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     */
    public function getList()
    {
        $this->setBrowseDefaults('id', 'asc');
        $resource = $this->params()->fromRoute('resource');
        $query = $this->params()->fromQuery();
        $response = $this->api->search($resource, $query);

        $this->paginator->setCurrentPage($query['page']);
        $this->paginator->setTotalCount($response->getTotalResults());

        // Add Link header for pagination.
        $links = [];
        $pages = [
            'first' => 1,
            'prev' => $this->paginator->getPreviousPage(),
            'next' => $this->paginator->getNextPage(),
            'last' => $this->paginator->getPageCount(),
        ];
        foreach ($pages as $rel => $page) {
            if ($page) {
                $query['page'] = $page;
                $url = $this->url()->fromRoute(null, [],
                    ['query' => $query, 'force_canonical' => true], true);
                $links[] = sprintf('<%s>; rel="%s"', $url, $rel);
            }
        }

        $this->getResponse()->getHeaders()
            ->addHeaderLine('Link', implode(', ', $links));
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     *
     * @param array $fileData PHP file upload data
     */
    public function create($data, $fileData = [])
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api->create($resource, $data, $fileData);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     */
    public function update($id, $data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api->update($resource, $id, $data);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     */
    public function patch($id, $data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api->update($resource, $id, $data, [], ['isPartial' => true]);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api->delete($resource, $id);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * Validate the API request and set global options.
     *
     * @param MvcEvent $event
     */
    public function onDispatch(MvcEvent $event)
    {
        $request = $this->getRequest();

        // Set pretty print.
        $prettyPrint = $request->getQuery('pretty_print');
        if (null !== $prettyPrint) {
            $this->setViewOption('pretty_print', true);
        }

        // Set the JSONP callback.
        $callback = $request->getQuery('callback');
        if (null !== $callback) {
            $this->setViewOption('callback', $callback);
        }

        try {
            // Finish dispatching the request.
            $this->checkContentType($request);
            parent::onDispatch($event);
        } catch (\Exception $e) {
            $this->logger()->err((string) $e);
            return $this->getErrorResult($event, $e);
        }
    }

    /**
     * Process post data and call create
     *
     * This method is overridden from the AbstractRestfulController to allow
     * processing of multipart POSTs.
     *
     * @param Request $request
     * @return mixed
     */
    public function processPostData(Request $request)
    {
        $contentType = $request->getHeader('content-type');
        if ($contentType->match('multipart/form-data')) {
            $content = $request->getPost('data');
            $fileData = $request->getFiles()->toArray();
        } else {
            $content = $request->getContent();
            $fileData = [];
        }
        $data = $this->jsonDecode($content);
        return $this->create($data, $fileData);
    }

    /**
     * Set a view model option.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setViewOption($key, $value)
    {
        $this->viewOptions[$key] = $value;
    }

    /**
     * Get all view options.
     *
     * return array
     */
    public function getViewOptions()
    {
        return $this->viewOptions;
    }

    /**
     * Check request content-type header to require JSON for methods with payloads.
     *
     * @param Request $request
     * @throws Exception\UnsupportedMediaTypeException
     */
    protected function checkContentType(Request $request)
    {
        // Require application/json Content-Type for certain methods.
        $method = strtolower($request->getMethod());
        $contentType = $request->getHeader('content-type');
        if (in_array($method, ['post', 'put', 'patch'])
            && (
                !$contentType
                || !$contentType->match(['application/json', 'multipart/form-data'])
            )
        ) {
            $contentType = $request->getHeader('Content-Type');
            $errorMessage = sprintf(
                'Invalid Content-Type header. Expecting "application/json", got "%s".',
                $contentType ? $contentType->getMediaType() : 'none'
            );

            throw new Exception\UnsupportedMediaTypeException($errorMessage);
        }
    }

    /**
     * Set an error result to the MvcEvent and return the result.
     *
     * @param MvcEvent $event
     * @param Exception $error
     */
    protected function getErrorResult(MvcEvent $event, \Exception $error)
    {
        $result = new ApiJsonModel(null, $this->getViewOptions());
        $result->setException($error);

        $event->setResult($result);
        return $result;
    }

    /**
     * Decode a JSON string.
     *
     * Override ZF's default to always use json_decode and to add error checking.'
     *
     * @param string
     * @return mixed
     * @throws Exception\DomainException if no JSON decoding functionality is
     *     available.
     */
    protected function jsonDecode($string)
    {
        $content = json_decode($string, (bool) $this->jsonDecodeType);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception\InvalidJsonException('JSON: ' . json_last_error_msg());
        }

        if (!is_array($content)) {
            throw new Exception\InvalidJsonException('JSON: Content must be an object or array.');
        }
        return $content;
    }
}

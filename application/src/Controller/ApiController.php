<?php
namespace Omeka\Controller;

use Omeka\Api\Response;
use Omeka\View\Model\ApiJsonModel;
use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\RequestInterface as Request;

class ApiController extends AbstractRestfulController
{
    /**
     * @var array
     */
    protected $viewOptions = array();

    /**
     * {@inheritDoc}
     */
    public function get($id)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->read($resource, $id);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     */
    public function getList()
    {
        $resource = $this->params()->fromRoute('resource');
        $data = $this->params()->fromQuery();
        $response = $this->api()->search($resource, $data);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     *
     * @param array $fileData PHP file upload data
     */
    public function create($data, $fileData = array())
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->create($resource, $data, $fileData);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     */
    public function update($id, $data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->update($resource, $id, $data);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     */
    public function patch($id, $data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->update($resource, $id, $data, array(), true);
        return new ApiJsonModel($response, $this->getViewOptions());
    }

    /**
     * {@inheritDoc}
     */
    public function delete($id)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->delete($resource, $id);
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

        // Require application/json Content-Type for certain methods.
        $method = strtolower($request->getMethod());
        $contentType = $request->getHeader('content-type');
        if (in_array($method, array('post', 'put', 'patch'))
            && !$contentType->match(array(
                'application/json',
                'multipart/form-data'))
        ) {
            $contentType = $request->getHeader('Content-Type');
            $errorMessage = sprintf(
                'Invalid Content-Type header. Expecting "application/json", got "%s".',
                $contentType ? $contentType->getMediaType() : 'none'
            );

            // Cause a 415 Unsupported Media Type response
            return $this->getErrorResult($event, $errorMessage, 415);
        }

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
            parent::onDispatch($event);
        } catch (\Exception $e) {
            $this->getServiceLocator()->get('Omeka\Logger')->err((string) $e);
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
            $fileData = null;
        }
        $data = Json::decode($content, $this->jsonDecodeType);
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
     * Set an error result to the MvcEvent and return the result.
     *
     * @param MvcEvent $event
     * @param string|Exception $error
     * @param integer $httpStatusCode Optional status code to explicitly set.
     *  If not set, the status code will be inferred from the exception type
     *  or set to 500 by default.
     */
    protected function getErrorResult(MvcEvent $event, $error, $httpStatusCode = null)
    {
        $response = new Response;
        $response->setStatus(Response::ERROR);

        if ($error instanceof \Exception) {
            $response->setException($error);
        } else {
            $response->addError(Response::ERROR, $error);
        }

        if ($httpStatusCode) {
            $options = array('status_code' => $httpStatusCode);
        } else {
            $options = array();
        }

        $result = new ApiJsonModel($response, $options);
        $event->setResult($result);
        return $result;
    }
}

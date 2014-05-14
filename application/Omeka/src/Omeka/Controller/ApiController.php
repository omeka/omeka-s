<?php
namespace Omeka\Controller;

use Omeka\Api\Response;
use Omeka\View\Model\ApiJsonModel;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;

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
     */
    public function create($data)
    {
        $resource = $this->params()->fromRoute('resource');
        $response = $this->api()->create($resource, $data);
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
        // Require application/json Content-Type for certain methods.
        $method = strtolower($this->getRequest()->getMethod());
        $contentType = $this->getRequest()->getHeader('Content-Type');
        if (in_array($method, array('post', 'put', 'patch'))
            && 'application/json' !== $contentType->getMediaType()
        ) {
            $response = new Response;
            $response->setStatus(Response::ERROR_BAD_REQUEST);
            $response->addError(Response::ERROR_BAD_REQUEST, sprintf(
                'Invalid Content-Type header. Expecting "application/json", got "%s".',
                $contentType->getMediaType()
            ));
            $return = new ApiJsonModel($response);
            $event->setResult($return);
            return $return;
        }

        // Set pretty print.
        $prettyPrint = $this->getRequest()->getQuery('pretty_print');
        if (null !== $prettyPrint) {
            $this->setViewOption('pretty_print', true);
        }

        // Set the JSON-P callback.
        $callback = $this->getRequest()->getQuery('callback');
        if (null !== $callback) {
            $this->setViewOption('callback', $callback);
        }

        // Finish dispatching the request.
        parent::onDispatch($event);
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
}

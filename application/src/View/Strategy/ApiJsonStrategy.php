<?php

namespace Omeka\View\Strategy;

use Omeka\Api\Exception as ApiException;
use Omeka\Api\Response;
use Omeka\Module;
use Omeka\Mvc\Exception as MvcException;
use Omeka\View\Model\ApiJsonModel;
use Omeka\View\Renderer\ApiJsonRenderer;
use Zend\View\Strategy\JsonStrategy;
use Zend\View\ViewEvent;

/**
 * View strategy for returning JSON from the API.
 */
class ApiJsonStrategy extends JsonStrategy
{
    /**
     * Constructor, sets the renderer object
     *
     * @param \Omeka\View\Renderer\ApiJsonRenderer
     */
    public function __construct(ApiJsonRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * {@inheritDoc}
     */
    public function selectRenderer(ViewEvent $e)
    {
        $model = $e->getModel();

        if (!$model instanceof ApiJsonModel) {
            // no JsonModel; do nothing
            return;
        }

        // JsonModel found
        return $this->renderer;
    }

    /**
     * {@inheritDoc}
     */
    public function injectResponse(ViewEvent $e)
    {
        // Test this again here to avoid running our extra code for non-API
        // requests.
        $renderer = $e->getRenderer();
        if ($renderer !== $this->renderer) {
            // Discovered renderer is not ours; do nothing
            return;
        }

        parent::injectResponse($e);

        $model = $e->getModel();
        $e->getResponse()->setStatusCode($this->getResponseStatusCode($model));
        $e->getResponse()->getHeaders()->addHeaderLine('Omeka-S-Version', Module::VERSION);
    }

    /**
     * Get the HTTP status code for an API response.
     *
     * @param Omeka\View\Model\ApiJsonModel $response
     * @return int
     */
    protected function getResponseStatusCode(ApiJsonModel $model)
    {
        $response = $model->getApiResponse();
        $exception = $model->getException();

        if ($response instanceof Response) {
            if (null === $response->getContent()) {
                return 204; // No Content
            }
            return 200; // OK
        } elseif ($exception instanceof \Exception) {
            return $this->getStatusCodeForException($exception);
        } else {
            return 200;
        }
    }

    /**
     * Get a status code based on the type of an exception (or lack thereof).
     *
     * @param \Exception|null $exception
     * @return int
     */
    protected function getStatusCodeForException(\Exception $exception = null)
    {
        if ($exception instanceof MvcException\InvalidJsonException) {
            return 400; // Bad Request
        }
        if ($exception instanceof ApiException\PermissionDeniedException) {
            return 403; // Forbidden
        }
        if ($exception instanceof ApiException\NotFoundException) {
            return 404; // Not Found
        }
        if ($exception instanceof MvcException\UnsupportedMediaTypeException) {
            return 415; // Unsupported Media Type
        }
        if ($exception instanceof ApiException\ValidationException) {
            return 422; // Unprocessable Entity
        }
        return 500; // Internal Server Error
    }
}

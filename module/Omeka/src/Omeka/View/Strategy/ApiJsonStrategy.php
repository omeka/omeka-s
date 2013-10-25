<?php

namespace Omeka\View\Strategy;

use Omeka\Api\Response;
use Omeka\View\Model\ApiJsonModel;
use Omeka\View\Renderer\ApiJsonRenderer;
use Zend\View\Strategy\JsonStrategy;
use Zend\View\ViewEvent;

/**
 * View strategy for returning JSON from the API.
 *
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
        $apiResponse = $e->getModel()->getApiResponse();
        $e->getResponse()->setStatusCode($this->getResponseStatusCode($apiResponse));
    }

    /**
     * Get the HTTP status code for an API response.
     *
     * @param \Omeka\Api\Response $response
     * @return integer
     */
    protected function getResponseStatusCode(Response $response)
    {
        switch ($response->getStatus()) {
            case Response::SUCCESS:
                return 200;
            case Response::ERROR_VALIDATION:
                return 422;
            case Response::ERROR_NOT_FOUND:
                return 404;
            case Response::ERROR_INTERNAL:
            default:
                return 500;
        }
    }
}

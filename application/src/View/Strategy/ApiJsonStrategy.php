<?php

namespace Omeka\View\Strategy;

use Omeka\Api\Exception as ApiException;
use Omeka\Api\Response;
use Omeka\Module;
use Omeka\Mvc\Exception as MvcException;
use Omeka\View\Model\ApiJsonModel;
use Omeka\View\Renderer\ApiJsonRenderer;
use Exception;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerInterface;
use Laminas\View\ViewEvent;

/**
 * View strategy for returning JSON from the API.
 *
 * Selects the renderer for ApiJsonModel requests and injects the response
 * with the rendered content, HTTP status code, Omeka-S-Version header,
 * and the correct Content-Type for the output format.
 */
class ApiJsonStrategy extends AbstractListenerAggregate
{
    protected $renderer;
    /**
     * Output formats and their media types.
     */
    protected $formats = [
        'rdfxml' => 'application/rdf+xml',
        'n3' => 'text/n3',
        'turtle' => 'text/turtle',
        'ntriples' => 'application/n-triples',
        'jsonld' => 'application/ld+json',
    ];

    protected $eventManager;

    /**
     * Constructor, sets the renderer object
     */
    public function __construct(ApiJsonRenderer $renderer, EventManager $eventManager)
    {
        $this->renderer = $renderer;
        $this->eventManager = $eventManager;
    }

    /**
     * Attach listeners for renderer selection and response injection.
     */
    public function attach(EventManagerInterface $events, $priority = 1)
    {
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RENDERER, [$this, 'selectRenderer'], $priority);
        $this->listeners[] = $events->attach(ViewEvent::EVENT_RESPONSE, [$this, 'injectResponse'], $priority);
    }

    /**
     * Return our renderer if the model is an ApiJsonModel, otherwise do nothing.
     */
    public function selectRenderer(ViewEvent $e)
    {
        $model = $e->getModel();

        if (!$model instanceof ApiJsonModel) {
            // no JsonModel; do nothing
            return;
        }

        // Set the output format to the renderer.
        $this->renderer->setFormat($this->getFormat($model));
        return $this->renderer;
    }

    /**
     * Inject the response with content, status code, and headers.
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

        $result = $e->getResult();
        $response = $e->getResponse();
        $headers = $response->getHeaders();

        if (is_string($result)) {
            $response->setContent($result);
        }

        $model = $e->getModel();
        $response->setStatusCode($this->getResponseStatusCode($model));
        $headers->addHeaderLine('Omeka-S-Version', Module::VERSION);

        // Add the correct Content-Type header for the output format.
        if ($this->renderer->hasJsonpCallback()) {
            $headers->addHeaderLine('Content-Type', 'text/javascript');
        } else {
            $headers->addHeaderLine('Content-Type', $this->formats[$this->getFormat($model)]);
        }
    }

    /**
     * Get the HTTP status code for an API response.
     */
    protected function getResponseStatusCode(ApiJsonModel $model): int
    {
        $response = $model->getApiResponse();
        $exception = $model->getException();

        if ($response instanceof Response) {
            if (null === $response->getContent()) {
                return 204; // No Content
            }
            return 200; // OK
        } elseif ($exception instanceof Exception) {
            return $this->getStatusCodeForException($exception);
        } else {
            return 200;
        }
    }

    /**
     * Get a status code based on the type of an exception (or lack thereof).
     */
    protected function getStatusCodeForException(?Exception $exception = null): int
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

    /**
     * Get the recognized output format.
     */
    protected function getFormat(ApiJsonModel $model): string
    {
        // Allow modules to register formats.
        $args = $this->eventManager->prepareArgs(['formats' => $this->formats]);
        $this->eventManager->trigger('api.output.formats', $this, $args);
        $this->formats = $args['formats'];

        // Prioritize the "format" query parameter.
        $format = $model->getOption('format');
        if ($format && array_key_exists($format, $this->formats)) {
            return $format;
        }
        // Respect the Accept header for content negotiation.
        $acceptHeader = $model->getOption('accept_header');
        if ($acceptHeader && $match = $acceptHeader->match(implode(', ', $this->formats))) {
            // May match against */* so double check allowed media types.
            if ($format = array_search($match->getRaw(), $this->formats)) {
                return $format;
            }
        }
        // The default output format is jsonld.
        return 'jsonld';
    }
}

<?php
namespace Omeka\View\Renderer;

use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Representation\RepresentationInterface;
use Omeka\Api\Response;
use Laminas\EventManager\EventManager;
use Laminas\Json\Json;
use Laminas\View\Renderer\JsonRenderer;

/**
 * JSON renderer for API responses.
 */
class ApiJsonRenderer extends JsonRenderer
{
    /**
     * @var bool
     */
    protected $hasJsonpCallback = false;

    /**
     * @var array The JSON-LD context
     */
    protected $context;

    /**
     * @var string The output format
     */
    protected $format;

    protected $eventManager;

    public function __construct(EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * Return whether the response is JSONP
     *
     * The view strategy checks this to decide what Content-Type to send, and
     * we need to provide a different implementation to preserve that signal
     * since we're handling JSONP manually here.
     *
     * @return bool
     */
    public function hasJsonpCallback()
    {
        return $this->hasJsonpCallback;
    }

    public function render($model, $values = null)
    {
        $response = $model->getApiResponse();
        $exception = $model->getException();

        if ($response instanceof Response) {
            $payload = $response->getContent();
        } elseif ($exception instanceof ValidationException) {
            $errors = $exception->getErrorStore()->getErrors();
            $payload = ['errors' => $errors];
        } elseif ($exception instanceof \Exception) {
            $payload = ['errors' => ['error' => $exception->getMessage()]];
        } else {
            $payload = $response;
        }

        if (null === $payload) {
            return null;
        }

        // Render a format that is not JSON-LD, if requested.
        if ('jsonld' !== $this->format) {
            // Render a single representation (get).
            if ($payload instanceof RepresentationInterface) {
                $jsonLd = $this->getJsonLdWithContext($payload);
                return $this->serializeJsonLdToFormat($jsonLd, $this->format);
            }
            // Render multiple representations (getList);
            if (is_array($payload) && isset($payload[0]) && $payload[0] instanceof RepresentationInterface) {
                $jsonLd = [];
                foreach ($payload as $representation) {
                    $jsonLd[] = $this->getJsonLdWithContext($representation);
                }
                return $this->serializeJsonLdToFormat($jsonLd, $this->format);
            }
        }

        $output = parent::render($payload);

        if ($payload instanceof RepresentationInterface) {
            $args = $this->eventManager->prepareArgs(['jsonLd' => $output]);
            $this->eventManager->trigger('rep.resource.json_output', $payload, $args);
            $output = $args['jsonLd'];
        }

        if (null !== $model->getOption('pretty_print')) {
            // Pretty print the JSON.
            $output = Json::prettyPrint($output);
        }

        $jsonpCallback = (string) $model->getOption('callback');
        if (!empty($jsonpCallback)) {
            // Wrap the JSON in a JSONP callback. Normally this would be done
            // via `$this->setJsonpCallback()` but we don't want to pass the
            // wrapped string to `rep.resource.json_output` handlers.
            $output = sprintf('%s(%s);', $jsonpCallback, $output);
            $this->hasJsonpCallback = true;
        }

        return $output;
    }

    /**
     * Get the JSON-LD array of a representation, adding the @context.
     *
     * @param RepresentationInterface $representation
     * @return array
     */
    public function getJsonLdWithContext(RepresentationInterface $representation)
    {
        // Add the @context by encoding the output as JSON, then decoding to an array.
        $jsonLd = Json::decode(Json::encode($representation), true);
        if (!$this->context) {
            // Get the JSON-LD @context
            $args = $this->eventManager->prepareArgs(['context' => []]);
            $this->eventManager->trigger('api.context', null, $args);
            $this->context = $args['context'];
        }
        $jsonLd['@context'] = $this->context;
        return $jsonLd;
    }

    /**
     * Serialize JSON-LD to another format.
     *
     * @param array $jsonLd
     * @param string $format
     * @param string
     */
    public function serializeJsonLdToFormat(array $jsonLd, string $format)
    {
        $output = null;
        if (in_array($format, ['rdfxml', 'n3', 'turtle', 'ntriples'])) {
            $graph = new \EasyRdf\Graph;
            $graph->parse(Json::encode($jsonLd), 'jsonld');
            $output = $graph->serialise($format);
        }
        // Allow modules to return custom output.
        $args = $this->eventManager->prepareArgs([
            'jsonLd' => $jsonLd,
            'output' => $output,
            'format' => $format,
        ]);
        $this->eventManager->trigger('api.output.serialize', $this, $args);
        return $args['output'];
    }

    /**
     * Set an alternate output format.
     *
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }
}

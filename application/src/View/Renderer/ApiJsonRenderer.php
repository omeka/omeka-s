<?php
namespace Omeka\View\Renderer;

use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Representation\RepresentationInterface;
use Omeka\Api\Response;
use Laminas\Json\Json;
use Laminas\View\Renderer\JsonRenderer;

/**
 * JSON renderer for API responses.
 */
class ApiJsonRenderer extends JsonRenderer
{
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

        $output = parent::render($payload);

        if ($payload instanceof RepresentationInterface) {
            $eventManager = $payload->getEventManager();
            $args = $eventManager->prepareArgs(['jsonLd' => $output]);
            $eventManager->trigger('rep.resource.json_output', $payload, $args);
            $output = $args['jsonLd'];
        }

        if (null !== $model->getOption('pretty_print')) {
            // Pretty print the JSON.
            $output = Json::prettyPrint($output);
        }

        $jsonpCallback = $model->getOption('callback');
        if (null !== $jsonpCallback) {
            // Wrap the JSON in a JSONP callback. Normally this would be done
            // via `$this->setJsonpCallback()` but we don't want to pass the
            // wrapped string to `rep.resource.json_output` handlers.
            $output = sprintf('%s(%s)', $jsonpCallback, $output);
        }

        return $output;
    }
}

<?php
namespace Omeka\View\Renderer;

use Omeka\Api\Exception\ValidationException;
use Omeka\Api\Response;
use Zend\Json\Json;
use Zend\View\Renderer\JsonRenderer;

/**
 * JSON renderer for API responses.
 */
class ApiJsonRenderer extends JsonRenderer
{
    /**
     * {@inheritDoc}
     */
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

        $jsonpCallback = $model->getOption('callback');
        if (null !== $jsonpCallback) {
            // Wrap the JSON in a JSONP callback.
            $this->setJsonpCallback($jsonpCallback);
        }

        $output = parent::render($payload);

        if (null !== $model->getOption('pretty_print')) {
            // Pretty print the JSON.
            $output = Json::prettyPrint($output);
        }

        return $output;
    }
}

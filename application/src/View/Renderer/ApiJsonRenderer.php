<?php
namespace Omeka\View\Renderer;

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
        $apiResponse = $model->getApiResponse();
        if ($apiResponse->isError()) {
            $errors = $apiResponse->getErrors();
            if (($e = $model->getException())) {
                $errors[$apiResponse->getStatus()] = $e->getMessage();
            }
            $payload = ['errors' => $errors];
        } else {
            $payload = $apiResponse->getContent();
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

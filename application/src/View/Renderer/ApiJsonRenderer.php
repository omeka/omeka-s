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

        $options = 0;
        if (null !== $model->getOption('pretty_print')) {
            $options = JSON_PRETTY_PRINT;
        }
        $output = json_encode($payload, $options);
        $callback = $model->getOption('callback');
        if (null !== $callback) {
            // Wrap JSON in JSONP callback.
            $output = $callback . '(' . $output . ');';
        }
        return $output;
    }
}

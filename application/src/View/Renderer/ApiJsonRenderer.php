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
        $response = $model->getApiResponse();

        if ($response instanceof \Omeka\Api\Response) {
            if ($response->isError()) {
                $errors = $response->getErrors();
                if (($e = $model->getException())) {
                    $errors[$response->getStatus()] = $e->getMessage();
                }
                $payload = ['errors' => $errors];
            } else {
                $payload = $response->getContent();
            }
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

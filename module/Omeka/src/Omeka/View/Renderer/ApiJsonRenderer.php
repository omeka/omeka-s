<?php
namespace Omeka\View\Renderer;

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
            $payload = array('errors' => $apiResponse->getErrors());
        } else {
            $payload = $apiResponse->getContent();
        }

        return parent::render($payload);
    }
}

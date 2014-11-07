<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Api\Response;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class ApiError extends AbstractPlugin
{
    /**
     * Detect API response errors and set up the response to account for them.
     *
     * @return null|array Null if no error. If there are validation error
     *  messages, they are returned as an array.
     */
    public function __invoke(Response $response)
    {
        if (!$response->isError()) {
            return null;
        }

        $controller = $this->getController();
        $httpResponse = $controller->getResponse();

        if ($response->getStatus() === Response::ERROR_VALIDATION) {
            $controller->messenger()->addError('There was an error during validation');
            return $response->getErrors();
        }

        // Rethrow any non-validation exception
        if (($e = $response->getException())) {
            throw $e;
        }

        return null;
    }
}

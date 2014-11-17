<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Api\Response;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class ApiError extends AbstractPlugin
{
    /**
     * Detect API response errors and set up the response to account for them.
     *
     * @return array An array of validation error messages, or an empty array.
     */
    public function __invoke(Response $response)
    {
        if (!$response->isError()) {
            return array();
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

        return array();
    }
}

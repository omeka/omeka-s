<?php
namespace Omeka\Mvc\Controller\Plugin;

use Omeka\Api\Response;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class ApiError extends AbstractPlugin
{
    /**
     * Detect API response errors and set up the response to account for them.
     *
     * @return boolean|array False if no error. If there are validation error
     *  messages, they are returned as an array.
     */
    public function __invoke(Response $response)
    {
        if (!$response->isError()) {
            return false;
        }

        $controller = $this->getController();
        $httpResponse = $controller->getResponse();

        switch ($response->getStatus()) {
            case Response::ERROR_NOT_FOUND:
                $httpResponse->setStatusCode(404);
                break;
            case Response::ERROR_PERMISSION_DENIED:
                $httpResponse->setStatusCode(403);
                break;
            case Response::ERROR_VALIDATION:
                $controller->messenger()->addError('There was an error during validation');
                return $response->getErrors();
            default:
                $httpResponse->setStatusCode(500);
        }
                
        return true;
    }
}

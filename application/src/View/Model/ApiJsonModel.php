<?php

namespace Omeka\View\Model;

use Zend\View\Model\JsonModel;

/**
 * View model for JSON responses from the API.
 */
class ApiJsonModel extends JsonModel
{
    /**
     * Key that stores the API response in the view variables
     */
    const API_RESPONSE_KEY = 'api_response';

    /**
     * Key that stores the thrown exception, if any, in the view variables
     */
    const EXCEPTION_KEY = 'exception';

    /**
     * Create a new model.
     *
     * The API response object can be passed here directly as the first
     * argument.
     *
     * @param \Omeka\Api\Response $apiResponse API response object
     * @param array|Traversable $options
     */
    public function __construct($apiResponse = null, $options = null)
    {
        $variables = [self::API_RESPONSE_KEY => $apiResponse];
        parent::__construct($variables, $options);
    }

    /**
     * Get the API response object stored on the model.
     *
     * @return \Omeka\Api\Response
     */
    public function getApiResponse()
    {
        return $this->getVariable(self::API_RESPONSE_KEY);
    }

    /**
     * Set the API response object on this model.
     *
     * @param \Omeka\Api\Response $apiResponse
     */
    public function setApiResponse($apiResponse)
    {
        $this->setVariable(self::API_RESPONSE_KEY, $apiResponse);
    }

    /**
     * Get the exception stored on the model.
     *
     * @return \Exception|null
     */
    public function getException()
    {
        return $this->getVariable(self::EXCEPTION_KEY);
    }

    /**
     * Set the exception on this model.
     *
     * @param \Exception $exception
     */
    public function setException(\Exception $exception)
    {
        $this->setVariable(self::EXCEPTION_KEY, $exception);
    }
}

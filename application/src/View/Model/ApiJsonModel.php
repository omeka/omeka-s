<?php

namespace Omeka\View\Model;

use Exception;
use Laminas\View\Model\ViewModel;

/**
 * View model for JSON responses from the API.
 *
 * Carries the API response object and any thrown exception through the
 * view layer to the renderer. Set as terminal to prevent layout wrapping.
 */
class ApiJsonModel extends ViewModel
{
    /**
     * Terminate rendering after this model to prevent layout wrapping.
     */
    protected $terminate = true;

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
     * @param \Omeka\Api\Response|array|null $apiResponse API response object
     * @param array|\Traversable $options
     */
    public function __construct($apiResponse = null, $options = null)
    {
        $variables = [self::API_RESPONSE_KEY => $apiResponse];
        parent::__construct($variables, $options);
    }

    /**
     * Get the API response object stored on the model.
     *
     * @return \Omeka\Api\Response|array|null
     */
    public function getApiResponse()
    {
        return $this->getVariable(self::API_RESPONSE_KEY);
    }

    /**
     * Set the API response object on this model.
     *
     * @param \Omeka\Api\Response|array|null $apiResponse
     */
    public function setApiResponse($apiResponse): void
    {
        $this->setVariable(self::API_RESPONSE_KEY, $apiResponse);
    }

    /**
     * Get the exception stored on the model.
     */
    public function getException(): ?Exception
    {
        return $this->getVariable(self::EXCEPTION_KEY);
    }

    /**
     * Set the exception on this model.
     */
    public function setException(Exception $exception): void
    {
        $this->setVariable(self::EXCEPTION_KEY, $exception);
    }
}

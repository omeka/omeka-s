<?php
namespace Omeka\Api;

use Omeka\Api\Request;
use Omeka\Stdlib\ErrorStore;
use Zend\Stdlib\Response as ZendResponse;

/**
 * Api response.
 */
class Response extends ZendResponse
{
    const SUCCESS          = 'success';
    const ERROR_INTERNAL   = 'error_internal';
    const ERROR_VALIDATION = 'error_validation';
    const ERROR_NOT_FOUND  = 'error_not_found';

    /**
     * @var array
     */
    protected $validStatuses = array(
        self::SUCCESS,
        self::ERROR_INTERNAL,
        self::ERROR_VALIDATION,
        self::ERROR_NOT_FOUND,
    );

    /**
     * @var array
     */
    protected $errorStatuses = array(
        self::ERROR_INTERNAL,
        self::ERROR_VALIDATION,
        self::ERROR_NOT_FOUND,
    );

    /**
     * Construct the API response.
     *
     * @param mixed $data
     * @param null|Request $request
     */
    public function __construct($content = null)
    {
        // Set the default metadata.
        $this->setMetadata('status', self::SUCCESS);
        $this->setMetadata('error_store', new ErrorStore);
        if (null !== $content) {
            $this->setContent($content);
        }
    }

    /**
     * Set the response status.
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        if (!in_array($status, $this->validStatuses)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The API does not support the "%s" response status.', 
                $status
            ));
        }
        $this->setMetadata('status', $status);
    }

    /**
     * Get the response status.
     * 
     * @return int
     */
    public function getStatus()
    {
        return $this->getMetadata('status');
    }

    /**
     * Merge errors of an ErrorStore.
     * 
     * @param array $errors
     */
    public function mergeErrors(ErrorStore $errorStore)
    {
        $this->getMetadata('error_store')->mergeErrors($errorStore);
    }

    /**
     * Add an error to the ErrorStore.
     *
     * @param string $key
     * @param string $message
     */
    public function addError($key, $message)
    {
        $this->getMetadata('error_store')->addError($key, $message);
    }

    /**
     * Get the error store.
     *
     * @return ErrorStore
     */
    public function getErrorStore()
    {
        return $this->getMetadata('error_store');
    }

    /**
     * Get the errors from the ErrorStore.
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->getMetadata('error_store')->getErrors();
    }

    /**
     * Check whether this response is an error.
     * 
     * @return bool
     */
    public function isError()
    {
        return in_array($this->getMetadata('status'), $this->errorStatuses);
    }

    /**
     * Set the request of this response.
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->setMetadata('request', $request);
    }

    /**
     * Get the request of this response.
     * 
     * @return Request
     */
    public function getRequest()
    {
        return $this->getMetadata('request');
    }

    /**
     * Set the total results of the query.
     *
     * @param int
     */
    public function setTotalResults($totalResults)
    {
        $this->setMetadata('total_results', $totalResults);
    }

    /**
     * Get the total results of the query.
     * 
     * @return int
     */
    public function getTotalResults()
    {
        return $this->getMetadata('total_results');
    }
}

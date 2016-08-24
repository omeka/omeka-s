<?php
namespace Omeka\Api;

use Omeka\Stdlib\ErrorStore;
use Zend\Stdlib\Response as ZendResponse;

/**
 * Api response.
 */
class Response extends ZendResponse
{
    const SUCCESS           = 'success';
    const ERROR             = 'error';
    const ERROR_VALIDATION  = 'error_validation';

    /**
     * @var array
     */
    protected $validStatuses = [
        self::SUCCESS,
        self::ERROR,
        self::ERROR_VALIDATION,
    ];

    /**
     * @var array
     */
    protected $errorStatuses = [
        self::ERROR,
        self::ERROR_VALIDATION,
    ];

    /**
     * @var mixed
     */
    protected $content = null;

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
     * Check whether a response status is valid.
     *
     * @return bool
     */
    public function isValidStatus($status)
    {
        return in_array($status, $this->validStatuses);
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
     * Check whether the request was successful.
     *
     * @return bool
     */
    public function isSuccess()
    {
        return self::SUCCESS === $this->getMetadata('status');
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

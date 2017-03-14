<?php
namespace Omeka\Api;

use Omeka\Stdlib\ErrorStore;

/**
 * Api response.
 */
class Response
{
    const SUCCESS = 'success';
    const ERROR = 'error';
    const ERROR_VALIDATION = 'error_validation';

    /**
     * @var array
     */
    protected $validStatuses = [self::SUCCESS, self::ERROR, self::ERROR_VALIDATION];

    /**
     * @var array
     */
    protected $errorStatuses = [self::ERROR, self::ERROR_VALIDATION];

    /**
     * @var string
     */
    protected $status = self::SUCCESS;

    /**
     * @var ErrorStore
     */
    protected $errorStore;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var int
     */
    protected $totalResults;

    /**
     * @var mixed
     */
    protected $content;

    /**
     * Construct the API response.
     *
     * @param mixed $data
     */
    public function __construct($content = null)
    {
        $this->errorStore = new ErrorStore;
        if (null !== $content) {
            $this->setContent($content);
        }
    }

    /**
     * Set the response status.
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get the response status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
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
     * Merge errorStore errors.
     *
     * @param ErrorStore $errorStore
     */
    public function mergeErrors(ErrorStore $errorStore)
    {
        $this->errorStore->mergeErrors($errorStore);
    }

    /**
     * Add an error to the ErrorStore.
     *
     * @param string $key
     * @param string $message
     */
    public function addError($key, $message)
    {
        $this->errorStore->addError($key, $message);
    }

    /**
     * Get the error store.
     *
     * @return ErrorStore
     */
    public function getErrorStore()
    {
        return $this->errorStore;
    }

    /**
     * Get the errors from the ErrorStore.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errorStore->getErrors();
    }

    /**
     * Check whether this response is an error.
     *
     * @return bool
     */
    public function isError()
    {
        return in_array($this->status, $this->errorStatuses);
    }

    /**
     * Check whether the request was successful.
     *
     * @return bool
     */
    public function isSuccess()
    {
        return self::SUCCESS === $this->status;
    }

    /**
     * Set the request of this response.
     *
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Get the request of this response.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set the total results of this response.
     *
     * @param int
     */
    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
    }

    /**
     * Get the total results of this response.
     *
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * Set request content.
     *
     * @param mixed $value
     */
    public function setContent($value)
    {
        $this->content = $value;
    }

    /**
     * Get request content.
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }
}

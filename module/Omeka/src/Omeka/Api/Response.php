<?php
namespace Omeka\Api;

use Omeka\Api\Request;
use Zend\Stdlib\Request as ZendResponse;

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
        // Set the default status.
        $this->setStatus(self::SUCCESS);
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
     * Set errors.
     *
     * Errors must be in the following format:
     * array(
     *     'foo' => array(
     *         'foo error message one',
     *         'foo error message two',
     *     ),
     *     'bar' => array(
     *         'bar error message one',
     *     ),
     * )
     *
     * @param array $errors
     */
    public function setErrors(array $errors)
    {
        foreach ($errors as $key => $messages) {
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    $this->setError($key, $message);
                }
            }
        }
    }

    /**
     * Set an error.
     *
     * @param string $key
     * @param string $message
     */
    public function setError($key, $message)
    {
        $errors = $this->getMetadata('errors', array());
        $errors[$key][] = $message;
        $this->setMetadata('errors', $errors);
    }

    /**
     * Get the errors.
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->getMetadata('errors');
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
}

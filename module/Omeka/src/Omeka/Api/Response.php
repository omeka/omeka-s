<?php
namespace Omeka\Api;

use Omeka\Api\Request;

/**
 * Api response.
 */
class Response
{
    const SUCCESS          = 'success';
    const ERROR_INTERNAL   = 'error_internal';
    const ERROR_VALIDATION = 'error_validation';
    const ERROR_NOT_FOUND  = 'error_not_found';

    /**
     * @var array
     */
    public static $validStatuses = array(
        self::SUCCESS,
        self::ERROR_INTERNAL,
        self::ERROR_VALIDATION,
        self::ERROR_NOT_FOUND,
    );

    /**
     * @var mixed
     */
    protected $data;

    /**
     * @var int
     */
    protected $status = self::SUCCESS;

    /**
     * @var array
     */
    protected $errors;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Construct the API response.
     *
     * @param mixed $data
     * @param null|Request $request
     */
    public function __construct($data = null)
    {
        if (null !== $data) {
            $this->data = $data;
        }
    }

    /**
     * Set the response data.
     *
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * Get the response data.
     * 
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the response status.
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        if (!in_array($status, self::$validStatuses)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'The API does not support the "%s" response status.', 
                $status
            ));
        }
        $this->status = $status;
    }

    /**
     * Get the response status.
     * 
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
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
            foreach ($messages as $message) {
                $this->setError($key, $message);
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
        $this->errors[$key][] = $message;
    }

    /**
     * Get the errors.
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
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
}

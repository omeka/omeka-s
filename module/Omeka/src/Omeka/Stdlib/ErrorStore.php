<?php
namespace Omeka\Stdlib;

/**
 * Error key/message store.
 */
class ErrorStore
{
    /**
     * @var array
     */
    protected $errors = array();

    /**
     * Add an error.
     *
     * @param string $key
     * @param string $message
     */
    public function addError($key, $message)
    {
        $this->errors[$key][] = $message;
    }

    /**
     * Add errors derived from Zend validator messages.
     *
     * @param array $errors
     * @param null|string $customKey
     */
    public function addValidatorMessages($key, array $messages)
    {
        foreach ($messages as $message) {
            $this->addError($key, $message);
        }
    }

    /**
     * Get errors.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Clear errors.
     */
    public function clearErrors()
    {
        $this->errors = array();
    }

    /**
     * Check whether the error store contains errors.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return (bool) count($this->errors);
    }
} 

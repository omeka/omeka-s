<?php
namespace Omeka\Error;

class Map
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
     * Add errors derived from Zend validators.
     *
     * @param array $errors
     * @param null|string $customKey
     */
    public function addValidatorErrors($customKey, array $errors)
    {
        foreach ($errors as $error) {
            $this->addError($customKey, $error);
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
     * Check whether the error map contains errors.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return (bool) count($this->errors);
    }
} 

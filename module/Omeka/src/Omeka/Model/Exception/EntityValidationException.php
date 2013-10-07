<?php
namespace Omeka\Model\Exception;

/**
 * Entity validation exception.
 */
class EntityValidationException extends RuntimeException
{
    /**
     * @var array
     */
    protected $validationErrors = array();

    /**
     * Add an entity validation error.
     *
     * @param string $key
     * @param string $message
     */
    public function addValidationError($key, $message)
    {
        $this->validationErrors[$key][] = $message;
    }

    /**
     * Get validation errors.
     *
     * @return array
     */
    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    /**
     * Clear validation errors.
     */
    public function clearValidationErrors()
    {
        $this->validationErrors = array();
    }
}

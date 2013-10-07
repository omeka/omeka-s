<?php
namespace Omeka\Model\Entity;

use Omeka\Model\Exception;

/**
 * Abstract entity.
 */
abstract class AbstractEntity implements EntityInterface
{
    /**
     * @var Exception\EntityValidationException
     */
    private $validationException;

    /**
     * Get the entity validation exception object.
     *
     * @return Exception\EntityValidationException
     */
    public function getValidationException()
    {
        // Set the exception if not already set.
        if (null === $this->validationException) {
            $this->validationException = new Exception\EntityValidationException;
        }
        return $this->validationException;
    }

    /**
     * Add an entity validation error to the validation exception.
     *
     * @param string $key
     * @param string $message
     */
    public function addValidationError($key, $message)
    {
        $this->getValidationException()->addValidationError($key, $message);
    }

    /**
     * Check whether this entity has validation errors.
     *
     * @return bool
     */
    public function hasValidationErrors()
    {
        return (bool) count($this->getValidationException()->getValidationErrors());
    }

    /**
     * Clear validation errors from the entity validation exception.
     */
    public function clearValidationErrors()
    {
        $this->getValidationException()->clearValidationErrors();
    }
}

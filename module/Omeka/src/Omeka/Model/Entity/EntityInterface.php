<?php
namespace Omeka\Model\Entity;

/**
 * Entity API adapter interface.
 */
interface EntityInterface
{
    /**
     * Get the entity validation exception object.
     *
     * @return Exception\EntityValidationException
     */
    public function getValidationException();

    /**
     * Set an entity validation error to the validation exception.
     *
     * @param string $key
     * @param string $message
     */
    public function setValidationError($key, $message);

    /**
     * Check whether this entity has validation errors.
     *
     * @return bool
     */
    public function hasValidationErrors();

    /**
     * Clear validation errors from the entity validation exception.
     */
    public function clearValidationErrors();
}

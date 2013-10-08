<?php
namespace Omeka\Model\Exception;

use Omeka\Error\Map as ErrorMap;

/**
 * Entity validation exception.
 */
class EntityValidationException extends RuntimeException
{
    /**
     * @var ErrorMap
     */
    protected $errorMap;

    /**
     * Set the error map.
     *
     * @param ErrorMap $errorMap
     */
    public function setErrorMap(ErrorMap $errorMap)
    {
        $this->errorMap = $errorMap;
    }

    /**
     * Get the error map.
     *
     * @return array
     */
    public function getErrorMap()
    {
        return $this->errorMap;
    }
}

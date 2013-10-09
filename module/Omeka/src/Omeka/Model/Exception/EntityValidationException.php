<?php
namespace Omeka\Model\Exception;

use Omeka\Stdlib\ErrorStore;

/**
 * Entity validation exception.
 */
class EntityValidationException extends RuntimeException
{
    /**
     * @var ErrorStore
     */
    protected $errorStore;

    /**
     * Set the error store.
     *
     * @param ErrorStore $errorStore
     */
    public function setErrorStore(ErrorStore $errorStore)
    {
        $this->errorStore = $errorStore;
    }

    /**
     * Get the error store.
     *
     * @return array
     */
    public function getErrorStore()
    {
        return $this->errorStore;
    }
}

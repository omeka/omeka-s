<?php
namespace Omeka\Api\Exception;

use Omeka\Stdlib\ErrorStore;

class ValidationException extends BadRequestException
{
    /**
     * @var ErrorStore
     */
    protected $errorStore;

    /**
     * Set the error store containing validation errors.
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
     * @return ErrorStore
     */
    public function getErrorStore()
    {
        // Set an error store instance in case one hasn't been set.
        if (!$this->errorStore instanceof ErrorStore) {
            $this->errorStore = new ErrorStore;
        }
        return $this->errorStore;
    }

    /**
     * Include underlying errors in exception string output
     *
     * @return string
     */
    public function __toString()
    {
        $format = "exception '%s' in %s:%s\nErrors:\n%s\nStack trace:\n%s";
        return sprintf($format,
            get_class($this),
            $this->getFile(),
            $this->getLine(),
            json_encode($this->getErrorStore()->getErrors(), JSON_PRETTY_PRINT),
            $this->getTraceAsString()
        );
    }
}

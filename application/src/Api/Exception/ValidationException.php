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

        // Flatten the error store messages and set the result as the exception
        // message.
        $message = '';
        foreach ($errorStore->getErrors() as $key => $messages) {
            $message .= "($key) " . implode(', ', $messages) . '; ';
        }
        $this->message = trim($message);
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
}

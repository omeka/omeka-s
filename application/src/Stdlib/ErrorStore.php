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
    protected $errors = [];

    /**
     * Add an error.
     *
     * @param string $key
     * @param string|Omeka\Stdlib\Message|array $message A message string, a
     * Message object, or a nested ErrorStore array structure.
     */
    public function addError($key, $message)
    {
        $this->errors[$key][] = $message;
    }

    /**
     * Merge errors of an ErrorStore onto this one.
     *
     * @param ErrorStore $errorStore
     * @param string $key Optional key to merge in errors under
     */
    public function mergeErrors(self $errorStore, $key = null)
    {
        if ($key === null) {
            foreach ($errorStore->getErrors() as $origKey => $messages) {
                if (is_array($messages)) {
                    foreach ($messages as $message) {
                        $this->addError($origKey, $message);
                    }
                }
            }
        } elseif ($errorStore->hasErrors()) {
            $this->addError($key, $errorStore->getErrors());
        }
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
        $this->errors = [];
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

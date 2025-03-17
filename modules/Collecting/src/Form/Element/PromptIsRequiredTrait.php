<?php
namespace Collecting\Form\Element;

/**
 * Flag a prompt element as required or optional.
 *
 * Prompt elements using this trait should implement InputProviderInterface and
 * set the "required" input spec accordingly in getInputSpecification().
 */
trait PromptIsRequiredTrait
{
    protected $required = false;

    public function setIsRequired($required)
    {
        $this->required = (bool) $required;
        $this->setAttribute('required', $this->required);
        return $this;
    }
}

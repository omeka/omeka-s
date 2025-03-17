<?php
namespace Collecting\Form\Element;

use Laminas\Form\Element\Select;

class PromptSelect extends Select
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        $spec = parent::getInputSpecification();
        $spec['required'] = $this->required;
        return $spec;
    }
}

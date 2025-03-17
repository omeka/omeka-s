<?php
namespace Collecting\Form\Element;

use Omeka\Form\Element\ResourceSelect;

class PromptItem extends ResourceSelect
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        $spec = parent::getInputSpecification();
        $spec['required'] = $this->required;
        return $spec;
    }
}

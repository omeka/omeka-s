<?php
namespace Collecting\Form\Element;

use Laminas\Form\Element\Url;

class PromptUrl extends Url
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        $spec = parent::getInputSpecification();
        $spec['required'] = $this->required;
        return $spec;
    }
}

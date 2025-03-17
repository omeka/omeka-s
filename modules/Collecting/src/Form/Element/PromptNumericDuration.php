<?php
namespace Collecting\Form\Element;

use NumericDataTypes\Form\Element\Duration as DurationElement;
use Laminas\InputFilter\InputProviderInterface;

class PromptNumericDuration extends DurationElement implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        return [
            'required' => $this->required,
        ];
    }
}

<?php
namespace Collecting\Form\Element;

use NumericDataTypes\Form\Element\Interval as IntervalElement;
use Laminas\InputFilter\InputProviderInterface;

class PromptNumericInterval extends IntervalElement implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        return [
            'required' => $this->required,
        ];
    }
}

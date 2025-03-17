<?php
namespace Collecting\Form\Element;

use NumericDataTypes\Form\Element\Integer as IntegerElement;
use Laminas\InputFilter\InputProviderInterface;

class PromptNumericInteger extends IntegerElement implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        return [
            'required' => $this->required,
        ];
    }
}

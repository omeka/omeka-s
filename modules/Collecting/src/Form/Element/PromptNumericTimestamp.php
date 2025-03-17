<?php
namespace Collecting\Form\Element;

use NumericDataTypes\Form\Element\Timestamp as TimestampElement;
use Laminas\InputFilter\InputProviderInterface;

class PromptNumericTimestamp extends TimestampElement implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        return [
            'required' => $this->required,
        ];
    }
}

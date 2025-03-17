<?php
namespace Collecting\Form\Element;

use Laminas\Form\Element\Textarea;
use Laminas\InputFilter\InputProviderInterface;

class PromptTextarea extends Textarea implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        return [
            'required' => $this->required,
        ];
    }
}

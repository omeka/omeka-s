<?php
namespace Collecting\Form\Element;

use Laminas\Form\Element\Email;
use Laminas\InputFilter\InputProviderInterface;

class PromptEmail extends Email implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        return [
            'required' => $this->required,
        ];
    }
}

<?php
namespace Collecting\Form\Element;

use Laminas\Form\Element\Text;
use Laminas\InputFilter\InputProviderInterface;

class PromptText extends Text implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    public function getInputSpecification() : array
    {
        return [
            'required' => $this->required,
        ];
    }
}

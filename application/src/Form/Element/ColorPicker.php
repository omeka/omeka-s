<?php
namespace Omeka\Form\Element;

use Zend\Form\Element\Text;
use Zend\InputFilter\InputProviderInterface;

class ColorPicker extends Text implements InputProviderInterface
{
    protected $attributes = [
        'type' => 'color_picker',
        'placeholder' => 'three- or six-digit hexadecimal form, or "transparent"',
    ];

    public function getInputSpecification()
    {
        return [
            'name' => $this->getName(),
            'required' => false,
            'validators' => [
                [
                    'name' => 'Callback',
                    'options' => [
                        'callback' => [$this, 'validateColor'],
                    ],
                ]
            ]
        ];
    }

    public function validateColor($color)
    {
        if ('' === $color) {
            return true;
        }
        if (preg_match('/^#([0-9a-f]{3}){1,2}$/i', $color) || 'transparent' === $color) {
            return true;
        }
        return false;
    }
}

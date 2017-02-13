<?php
namespace Omeka\Form\Element;

use Zend\Form\Element\Text;
use Zend\InputFilter\InputProviderInterface;

class ColorPicker extends Text implements InputProviderInterface
{
    const REGEX = '#([0-9A-Fa-f]{3}){1,2}|transparent';
    const REGEX_EXPLANATION = 'three- or six-digit hexadecimal color, or "transparent"'; // @translate

    protected $attributes = [
        'type' => 'color_picker',
        'placeholder' => self::REGEX_EXPLANATION,
        'title' => self::REGEX_EXPLANATION,
        'pattern' => self::REGEX,
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
                        'message' => 'Invalid color format', // @translate
                    ],
                ],
            ],
        ];
    }

    public function validateColor($color)
    {
        if ('' === $color) {
            return true;
        }
        return (bool) preg_match('/^(?:' . self::REGEX . ')$/', $color);
    }
}

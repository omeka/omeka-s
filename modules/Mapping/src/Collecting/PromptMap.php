<?php
namespace Mapping\Collecting;

use Collecting\Form\Element\PromptIsRequiredTrait;
use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class PromptMap extends Element implements InputProviderInterface
{
    use PromptIsRequiredTrait;

    protected $attributes = [
        'type' => 'promptMap',
    ];

    public function getInputSpecification()
    {
        return [
            'required' => $this->required,
            'validators' => [
                [
                    'name' => 'Callback',
                    'options' => [
                        'callback' => [$this, 'isValid'],
                        'messages' => [
                            'callbackValue' => 'You must select a location on the map.', // @translate
                        ],
                    ],
                ],
            ],
        ];
    }

    public function isValid($value)
    {
        if (!$this->required) {
            return true;
        }
        if (is_numeric($value['lat']) && is_numeric($value['lat'])) {
            return true;
        }
        return false;
    }
}

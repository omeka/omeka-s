<?php
namespace Omeka\Form\Element;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;

class VocabularyAdvanced extends Fieldset implements InputFilterProviderInterface
{
    public function init()
    {
        $this->setLabel('Advanced'); // @translate

        $this->add([
            'name' => 'lang',
            'type' => 'text',
            'options' => [
                'label' => 'Preferred language', // @translate
                'info' => 'Enter the preferred language of the labels and comments using an <a target="_blank" href="https://www.w3.org/International/articles/language-tags/">IETF language tag</a>. Defaults to the first available.', // @translate
                'escape_info' => false,
            ],
            'attributes' => [
                'id' => 'lang',
            ],
        ]);
        $this->add([
            'name' => 'label_property',
            'type' => 'text',
            'options' => [
                'label' => 'Preferred label property', // @translate
                'info' => 'Enter the preferred label property. This is typically only needed if the vocabulary uses an unconventional property for labels. Please use the full property URI enclosed in angle brackets.', // @translate
            ],
            'attributes' => [
                'id' => 'label_property',
            ],
        ]);
        $this->add([
            'name' => 'comment_property',
            'type' => 'text',
            'options' => [
                'label' => 'Preferred comment property', // @translate
                'info' => 'Enter the preferred comment property. This is typically only needed if the vocabulary uses an unconventional property for comments. Please use the full property URI enclosed in angle brackets.', // @translate
            ],
            'attributes' => [
                'id' => 'comment_property',
            ],
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [
            [
                'name' => 'label_property',
                'required' => false,
                'filters' => [
                    [
                        'name' => 'ToNull',
                    ],
                ],
            ],
            [
                'name' => 'comment_property',
                'required' => false,
                'filters' => [
                    [
                        'name' => 'ToNull',
                    ],
                ],
            ],
        ];
    }
}

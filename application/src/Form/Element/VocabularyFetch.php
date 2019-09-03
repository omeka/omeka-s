<?php
namespace Omeka\Form\Element;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;

class VocabularyFetch extends Fieldset implements InputFilterProviderInterface
{
    public function init()
    {
        $this->add([
            'name' => 'file',
            'type' => 'file',
            'options' => [
                'label' => 'Vocabulary file', // @translate
                'info' => 'Choose a RDF vocabulary file. You must choose a file or enter a URL below.', // @translate
            ],
            'attributes' => [
                'id' => 'file',
            ],
        ]);
        $this->add([
            'name' => 'url',
            'type' => 'url',
            'options' => [
                'label' => 'Vocabulary URL', // @translate
                'info' => 'Enter a RDF vocabulary URL. You must enter a URL or choose a file above.', // @translate
            ],
            'attributes' => [
                'id' => 'url',
            ],
        ]);
        $this->add([
            'name' => 'format',
            'type' => 'Select',
            'options' => [
                'label' => 'File format', // @translate
                'value_options' => [
                    'guess' => '[Autodetect]', // @translate
                    'jsonld' => 'JSON-LD (.jsonld)', // @translate
                    'ntriples' => 'N-Triples (.nt)', // @translate
                    'n3' => 'Notation3 (.n3)', // @translate
                    'rdfxml' => 'RDF/XML (.rdf)', // @translate
                    'turtle' => 'Turtle (.ttl)', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'format',
                'class' => 'chosen-select',
            ],
        ]);
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
                'name' => 'url',
                'required' => false,
            ],
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

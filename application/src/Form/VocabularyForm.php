<?php
namespace Omeka\Form;

use Zend\Form\Form;

class VocabularyForm extends Form
{
    protected $options = [
        'include_namespace' => false,
    ];

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, array_merge($this->options, $options));
    }

    public function init()
    {
        $this->add([
            'name' => 'vocabulary-info',
            'type' => 'fieldset',
            'options' => [
                'label' => 'Basic info', // @translate
            ],
        ]);
        $this->add([
            'name' => 'vocabulary-file',
            'type' => 'fieldset',
            'options' => [
                'label' => 'File', // @translate
            ],
        ]);
        $this->add([
            'name' => 'vocabulary-advanced',
            'type' => 'fieldset',
            'options' => [
                'label' => 'Advanced', // @translate
            ],
        ]);

        $this->get('vocabulary-info')->add([
            'name' => 'o:label',
            'type' => 'text',
            'options' => [
                'label' => 'Label', // @translate
                'info' => 'Enter a human-readable title of the vocabulary.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:label',
            ],
        ]);
        $this->get('vocabulary-info')->add([
            'name' => 'o:comment',
            'type' => 'textarea',
            'options' => [
                'label' => 'Comment', // @translate
                'info' => 'Enter a human-readable description of the vocabulary.', // @translate
            ],
            'attributes' => [
                'id' => 'o:comment',
            ],
        ]);
        if ($this->getOption('include_namespace')) {
            $this->get('vocabulary-info')->add([
                'name' => 'o:namespace_uri',
                'type' => 'text',
                'options' => [
                    'label' => 'Namespace URI', // @translate
                    'info' => 'Enter the unique namespace URI used to identify the classes and properties of the vocabulary.', // @translate
                ],
                'attributes' => [
                    'required' => true,
                    'id' => 'o:namespace_uri',
                ],
            ]);
            $this->get('vocabulary-info')->add([
                'name' => 'o:prefix',
                'type' => 'text',
                'options' => [
                    'label' => 'Namespace prefix', // @translate
                    'info' => 'Enter a concise vocabulary identifier used as a shorthand for the namespace URI.', // @translate
                ],
                'attributes' => [
                    'required' => true,
                    'id' => 'o:prefix',
                ],
            ]);
        }
        $this->get('vocabulary-file')->add([
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
        $this->get('vocabulary-file')->add([
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
        $this->get('vocabulary-file')->add([
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
        $this->get('vocabulary-advanced')->add([
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
        $this->get('vocabulary-advanced')->add([
            'name' => 'label_property',
            'type' => 'text',
            'options' => [
                'label' => 'Label property', // @translate
                'info' => 'Enter the label property. This is typically only needed if the vocabulary uses an unconventional property for labels. Please use the full property URI enclosed in angle brackets.', // @translate
            ],
            'attributes' => [
                'id' => 'label_property',
            ],
        ]);
        $this->get('vocabulary-advanced')->add([
            'name' => 'comment_property',
            'type' => 'text',
            'options' => [
                'label' => 'Comment property', // @translate
                'info' => 'Enter the comment property. This is typically only needed if the vocabulary uses an unconventional property for comments. Please use the full property URI enclosed in angle brackets.', // @translate
            ],
            'attributes' => [
                'id' => 'comment_property',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->get('vocabulary-file')->add([
            'name' => 'url',
            'required' => false,
        ]);
        $inputFilter->get('vocabulary-advanced')->add([
            'name' => 'label_property',
            'required' => false,
            'filters' => [
                ['name' => 'ToNull'],
            ],
        ]);
        $inputFilter->get('vocabulary-advanced')->add([
            'name' => 'comment_property',
            'required' => false,
            'filters' => [
                ['name' => 'ToNull'],
            ],
        ]);
    }
}

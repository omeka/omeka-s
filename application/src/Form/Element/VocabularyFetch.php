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
                'info' => 'Choose a RDF vocabulary file. You must choose a file or enter a URL.', // @translate
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
                'info' => 'Enter a RDF vocabulary URL. You must enter a URL or choose a file.', // @translate
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
                'label' => 'Language', // @translate
                'info' => 'Enter the preferred language of the labels and comments using an IETF language tag. Defaults to the first available.', // @translate
            ],
            'attributes' => [
                'id' => 'lang',
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
        ];
    }
}

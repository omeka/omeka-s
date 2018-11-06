<?php
namespace Omeka\Form;

use Zend\Form\Form;

class VocabularyImportForm extends Form
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
            'name' => 'o:prefix',
            'type' => 'text',
            'options' => [
                'label' => 'Prefix', // @translate
                'info' => 'A concise vocabulary identifier, used as a shorthand proxy for the namespace URI.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:prefix',
            ],
        ]);

        $this->add([
            'name' => 'o:namespace_uri',
            'type' => 'text',
            'options' => [
                'label' => 'Namespace URI', // @translate
                'info' => 'The unique namespace URI used by the vocabulary to identify local member classes and properties.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:namespace_uri',
            ],
        ]);

        $this->add([
            'name' => 'o:label',
            'type' => 'text',
            'options' => [
                'label' => 'Label', // @translate
                'info' => 'A human-readable title of the vocabulary.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:label',
            ],
        ]);

        $this->add([
            'name' => 'o:comment',
            'type' => 'textarea',
            'options' => [
                'label' => 'Comment', // @translate
                'info' => 'A human-readable description of the vocabulary.', // @translate
            ],
            'attributes' => [
                'id' => 'o:comment',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'url',
            'required' => false,
        ]);
    }
}

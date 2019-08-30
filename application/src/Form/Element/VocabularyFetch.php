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
        $this->add([
            'name' => 'label_property',
            'type' => 'text',
            'options' => [
                'label' => 'Label property', // @translate
                'info' => sprintf('Enter the preferred label property. You can use an unconventional property by entering the namespace and the property name separated by a space. Defaults to %s.', \Omeka\Stdlib\RdfImporter::DEFAULT_LABEL_PROPERTY), // @translate
            ],
            'attributes' => [
                'id' => 'label_property',
            ],
        ]);
        $this->add([
            'name' => 'comment_property',
            'type' => 'text',
            'options' => [
                'label' => 'Comment property', // @translate
                'info' => sprintf('Enter the preferred comment property. You can use an unconventional property by entering the namespace and the property name separated by a space. Defaults to %s.', \Omeka\Stdlib\RdfImporter::DEFAULT_COMMENT_PROPERTY), // @translate
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
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'propertyFilter'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'comment_property',
                'required' => false,
                'filters' => [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'propertyFilter'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Prepare a preferred property for use by the RDF importer.
     *
     * @param string $property
     * @return string|array
     */
    public function propertyFilter($property)
    {
        $property = trim($property);
        if ('' === $property) {
            return null;
        }
        $property = explode(' ', $property);
        if (2 === count($property)) {
            return $property;
        }
        return $property[0];
    }
}

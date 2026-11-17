<?php
namespace Omeka\Form;

use Laminas\Form\Form;
use Omeka\Entity\Vocabulary;

class VocabularyForm extends Form
{
    protected $translator;

    /**
     * - vocabulary: (optional) The vocabulary to change. Without one, the
     *   form imports a vocabulary.
     * - mode: (optional) With a vocabulary, what the form changes: "edit"
     *   (the default), its label and comment; "change_namespace", its
     *   namespace URI; or "update_from_file", its classes and properties,
     *   from a file
     */
    protected $options = [
        'vocabulary' => null,
        'mode' => 'edit',
    ];

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, array_merge($this->options, $options));
    }

    public function init()
    {
        $vocabulary = $this->getOption('vocabulary');
        $mode = $vocabulary ? $this->getOption('mode') : null;
        // An import has every part; each change to a vocabulary, its own.
        // How the file is read is chosen on the review of an update, where
        // its changes show.
        $hasLabel = !$vocabulary || 'edit' === $mode;
        $hasNamespace = !$vocabulary || 'change_namespace' === $mode;
        $hasInfo = $hasLabel || $hasNamespace;
        $hasFile = !$vocabulary || 'update_from_file' === $mode;
        $hasAdvanced = !$vocabulary;
        $this->setAttribute('id', 'vocabulary-form');
        if ($hasInfo) {
            $this->add([
                'name' => 'vocabulary-info',
                'type' => 'fieldset',
                'options' => [
                    'label' => 'Basic info', // @translate
                ],
            ]);
            $this->addInfoElements($vocabulary, $hasLabel, $hasNamespace);
        }
        if ($hasFile) {
            $this->add([
                'name' => 'vocabulary-file',
                'type' => 'fieldset',
                'options' => [
                    'label' => 'File', // @translate
                ],
            ]);
            $this->addFileElements();
        }
        if ($hasAdvanced) {
            $this->add([
                'name' => 'vocabulary-advanced',
                'type' => 'fieldset',
                'options' => [
                    'label' => 'Advanced', // @translate
                ],
            ]);
            $this->addAdvancedElements();
        }

        $inputFilter = $this->getInputFilter();
        if ($hasNamespace) {
            // The file is read with the namespace URI before the API saves
            // it, trimmed, so a stray space would find no classes or
            // properties.
            $inputFilter->get('vocabulary-info')->add([
                'name' => 'o:namespace_uri',
                'required' => true,
                'filters' => [
                    ['name' => 'StringTrim'],
                ],
            ]);
        }
        if (!$vocabulary) {
            $inputFilter->get('vocabulary-info')->add([
                'name' => 'o:prefix',
                'validators' => [
                    [
                        'name' => 'Regex',
                        'options' => [
                            'pattern' => Vocabulary::PREFIX_REGEX,
                            'messages' => [
                                \Laminas\Validator\Regex::NOT_MATCH => 'The prefix may only contain letters, numbers, hyphens, and underscores.', // @translate
                            ],
                        ],
                    ],
                ],
            ]);
        }
        if ($hasFile) {
            $inputFilter->get('vocabulary-file')->add([
                'name' => 'url',
                'required' => false,
            ]);
        }
        if ($hasAdvanced) {
            $inputFilter->get('vocabulary-advanced')->add([
                'name' => 'comments',
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

    /**
     * Add the vocabulary's own fields.
     *
     * @param \Omeka\Api\Representation\VocabularyRepresentation|null $vocabulary
     * @param bool $hasLabel Whether to add its label and comment
     * @param bool $hasNamespace Whether to add its namespace URI
     */
    protected function addInfoElements($vocabulary, $hasLabel, $hasNamespace)
    {
        if ($hasLabel) {
            $this->addLabelElements();
        }
        if ($hasNamespace) {
            $this->get('vocabulary-info')->add([
                'name' => 'o:namespace_uri',
                'type' => 'text',
                'options' => [
                    // A vocabulary's own is shown above the field for its new one.
                    'label' => $vocabulary
                        ? 'New namespace URI' // @translate
                        : 'Namespace URI', // @translate
                    'info' => 'Enter the unique namespace URI used to identify the classes and properties of the vocabulary.', // @translate
                ],
                'attributes' => [
                    'required' => true,
                    'id' => 'o:namespace_uri',
                    'data-confirm-ends-with' => $this->translator->translate('The namespace URI you entered does not end with a / or #, as is normally expected for namespace URIs. Would you like to save anyway?'),
                ],
            ]);
        }
        if (!$vocabulary) {
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
    }

    /**
     * Add the vocabulary's label and comment.
     */
    protected function addLabelElements()
    {
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
    }

    /**
     * Add the fields of the file to read.
     */
    protected function addFileElements()
    {
        $this->get('vocabulary-file')->add([
            'name' => 'import_type',
            'type' => 'radio',
            'options' => [
                'label' => 'Import type', // @translate
                'value_options' => [
                    'upload' => 'Upload', // @translate
                    'url' => 'URL', // @translate
                ],
            ],
            'attributes' => [
                'value' => 'upload',
                'class' => 'import-type-select',
            ],
        ]);
        $this->get('vocabulary-file')->add([
            'name' => 'file',
            'type' => 'file',
            'options' => [
                'label' => 'File upload', // @translate
                'info' => 'Choose a RDF vocabulary file.', // @translate
            ],
            'attributes' => [
                'id' => 'file-upload',
            ],
        ]);
        $this->get('vocabulary-file')->add([
            'name' => 'url',
            'type' => 'url',
            'options' => [
                'label' => 'File URL', // @translate
                'info' => 'Enter a URL to a RDF vocabulary file.', // @translate
            ],
            'attributes' => [
                'id' => 'file-url',
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
                    'rdfxml' => 'RDF/XML (.rdf)', // @translate
                    'turtle' => 'Turtle (.ttl)', // @translate
                    [
                        'value' => 'turtle',
                        'label' => 'Notation3 (.n3)', // @translate
                    ],
                ],
            ],
            'attributes' => [
                'id' => 'format',
                'class' => 'chosen-select',
            ],
        ]);
    }

    /**
     * Add the options of how an imported file is read.
     */
    protected function addAdvancedElements()
    {
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
            'name' => 'comments',
            'type' => 'select',
            'options' => [
                'label' => 'Comments', // @translate
                'info' => 'Where a term\'s comment is read from. Omeka can use its definition (skos:definition) in place of rdfs:comment. A comment property entered below is used instead.', // @translate
                'value_options' => [
                    'comment' => 'Comment', // @translate
                    'definition' => 'Definition where there is one, else comment', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'comments',
                'value' => 'comment',
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
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }
}

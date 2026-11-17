<?php
namespace Omeka\Form;

use Laminas\Form\Form;
use Omeka\Entity\Vocabulary;

class VocabularyForm extends Form
{
    /**
     * The formats a vocabulary file can be read in, with their labels, as
     * the File format field offers them.
     */
    const FORMATS = [
        'guess' => '[Autodetect]', // @translate
        'jsonld' => 'JSON-LD (.jsonld)', // @translate
        'ntriples' => 'N-Triples (.nt)', // @translate
        'rdfxml' => 'RDF/XML (.rdf)', // @translate
        // One option: EasyRdf reads Notation3 as Turtle, and two options of
        // one value would both show as chosen.
        'turtle' => 'Turtle or Notation3 (.ttl, .n3)', // @translate
    ];

    protected $translator;

    /**
     * - vocabulary: (optional) The vocabulary to change, if any.
     * - mode: (optional) "edit", a vocabulary's own fields (the default
     *   with a vocabulary); "file", the file to import or update from (the
     *   default without one); or "import_details", an import's second step.
     * - languages: (optional) For "import_details", the file's languages
     */
    protected $options = [
        'vocabulary' => null,
        'mode' => null,
        'languages' => [],
    ];

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, array_merge($this->options, $options));
    }

    public function init()
    {
        $vocabulary = $this->getOption('vocabulary');
        $mode = $this->getOption('mode') ?? ($vocabulary ? 'edit' : 'file');
        $hasInfo = in_array($mode, ['edit', 'import_details'], true);
        $hasPrefix = 'import_details' === $mode;
        $hasFile = 'file' === $mode;
        $hasAdvanced = 'import_details' === $mode;
        $this->setAttribute('id', 'vocabulary-form');
        if ($hasInfo) {
            $this->add([
                'name' => 'vocabulary-info',
                'type' => 'fieldset',
                'options' => [
                    'label' => 'Basic info', // @translate
                ],
            ]);
            $this->addInfoElements($vocabulary, $hasPrefix);
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
            $this->addAdvancedElements($this->getOption('languages'));
        }

        $inputFilter = $this->getInputFilter();
        if ($hasInfo) {
            // Trimmed before the file is read with it, as the API trims it.
            $inputFilter->get('vocabulary-info')->add([
                'name' => 'o:namespace_uri',
                'required' => true,
                'filters' => [
                    ['name' => 'StringTrim'],
                ],
            ]);
        }
        if ($hasPrefix) {
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
                'name' => 'lang',
                'required' => false,
            ]);
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
     * @param bool $hasPrefix Whether to add its prefix
     */
    protected function addInfoElements($vocabulary, $hasPrefix)
    {
        $this->addLabelElements();
        $this->get('vocabulary-info')->add([
            'name' => 'o:namespace_uri',
            'type' => 'text',
            'options' => [
                'label' => 'Namespace URI', // @translate
                'info' => $vocabulary
                    ? 'Enter the unique namespace URI used to identify the classes and properties of the vocabulary. Changing it changes the URI of every class and property in this vocabulary.' // @translate
                    : 'Enter the unique namespace URI used to identify the classes and properties of the vocabulary.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:namespace_uri',
                'data-confirm-ends-with' => $this->translator->translate('The namespace URI you entered does not end with a / or #, as is normally expected for namespace URIs. Would you like to save anyway?'),
                // vocabulary-form.js skips its "doesn't end with / or #"
                // prompt for this namespace URI, unchanged.
                'data-saved-namespace-uri' => $vocabulary ? $vocabulary->namespaceUri() : null,
            ],
        ]);
        if ($hasPrefix) {
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
                'value_options' => self::FORMATS,
            ],
            'attributes' => [
                'id' => 'format',
                'class' => 'chosen-select',
            ],
        ]);
    }

    /**
     * Add the options of how an imported file is read.
     *
     * @param string[] $languages The file's languages
     */
    protected function addAdvancedElements(array $languages)
    {
        $languageOptions = ['' => 'First available']; // @translate
        foreach ($languages as $tag) {
            $name = extension_loaded('intl') ? \Locale::getDisplayName($tag) : $tag;
            $languageOptions[$tag] = $name === $tag ? $tag : sprintf('%s (%s)', $name, $tag);
        }
        $this->get('vocabulary-advanced')->add([
            'name' => 'lang',
            'type' => 'select',
            'options' => [
                'label' => 'Preferred language', // @translate
                'info' => 'The language of the labels and comments to use, where the file has them in more than one.', // @translate
                'value_options' => $languageOptions,
            ],
            'attributes' => [
                'id' => 'lang',
            ],
        ]);
        // vocabulary-form.js shows the comment property only when
        // "Another property…" is chosen.
        $this->get('vocabulary-advanced')->add([
            'name' => 'comments',
            'type' => 'select',
            'options' => [
                'label' => 'Comments from', // @translate
                'info' => 'Each class and property takes its comment from its comment (rdfs:comment) or its definition (skos:definition, dcterms:description, or dc11:description), whichever it has. Choose which to use when it has both, or another property to take it from.', // @translate
                'value_options' => [
                    'comment' => 'Comment, else definition', // @translate
                    'definition' => 'Definition, else comment', // @translate
                    'other' => 'Another property…', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'comments',
                'value' => 'comment',
                'data-other-property' => '#comment_property',
            ],
        ]);
        $this->get('vocabulary-advanced')->add([
            'name' => 'comment_property',
            'type' => 'text',
            'options' => [
                'label' => 'Comment property', // @translate
                'info' => 'Enter the comment property. Please use the full property URI enclosed in angle brackets.', // @translate
            ],
            'attributes' => [
                'id' => 'comment_property',
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
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }
}

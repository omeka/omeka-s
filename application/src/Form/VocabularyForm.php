<?php
namespace Omeka\Form;

use Laminas\Form\Form;
use Omeka\Entity\Vocabulary;
use Omeka\Stdlib\RdfImporter;

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

    /**
     * The fields that choose how a file is read: on the review page by these
     * names, and on import details in the Advanced fieldset.
     */
    const CHOICE_FIELDS = ['lang', 'other_lang', 'labels', 'label_property', 'comments', 'comment_property'];

    protected $translator;

    /**
     * - vocabulary: (optional) The vocabulary to change, if any.
     * - mode: (optional) "edit", a vocabulary's own fields (the default
     *   with a vocabulary); "file", the file to import or update from (the
     *   default without one); "import_details", an import's second step; or
     *   "review", the import options a review's changes are shown with
     * - languages: (optional) For "import_details" and "review", the
     *   languages to choose from: the file's, and for "review" also the one
     *   chosen, should the file no longer have it
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
        $isReview = 'review' === $mode;
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
                    // How the classes and properties are read, under this
                    // label on import details and the review alike.
                    'label' => 'Classes and properties', // @translate
                ],
            ]);
            // Offered for a file with no languages too: another language
            // pairs with a label or comment property whose languages the
            // file's default ones don't show.
            $this->addAdvancedElements($this->getOption('languages'), $this->get('vocabulary-advanced'));
        }
        if ($isReview) {
            // The review page sends its choices by GET, as core's search
            // forms do, so without CSRF, and under their own names, as
            // ?lang=fr.
            $this->remove($this->getName() ? sprintf('%s_csrf', $this->getName()) : 'csrf');
            $this->addAdvancedElements($this->getOption('languages'), $this);
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
                // Trimmed, as the API trims it, so " bibo " isn't refused.
                'filters' => [
                    ['name' => 'StringTrim'],
                ],
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
                'name' => 'file',
                'type' => \Laminas\InputFilter\FileInput::class,
                'required' => false,
                'validators' => [
                    [
                        'name' => \Laminas\Validator\File\UploadFile::class,
                        'options' => [
                            'messages' => [
                                // In place of the validator's own, which names
                                // php.ini's upload_max_filesize.
                                \Laminas\Validator\File\UploadFile::INI_SIZE => 'The file is larger than this server accepts for uploads. Choose URL and enter the file\'s address instead.', // @translate
                            ],
                        ],
                    ],
                ],
            ]);
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
                'name' => 'other_lang',
                'required' => false,
            ]);
            $inputFilter->get('vocabulary-advanced')->add([
                'name' => 'labels',
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
                    'info' => 'Enter a concise vocabulary identifier used as a shorthand for the namespace URI. It can\'t be changed after importing.', // @translate
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
     * @param \Laminas\Form\FieldsetInterface $fieldset Where to add them: the
     *   Advanced fieldset, or the review page's form itself
     */
    protected function addAdvancedElements(array $languages, $fieldset)
    {
        $languageOptions = ['' => 'First available']; // @translate
        foreach ($languages as $tag) {
            $name = extension_loaded('intl') ? \Locale::getDisplayName($tag) : $tag;
            $languageOptions[$tag] = $name === $tag ? $tag : sprintf('%s (%s)', $name, $tag);
        }
        // Any language, as for a label or comment property whose languages
        // the list doesn't show: the list has only those of the file's
        // default label and comment properties.
        $languageOptions['other'] = 'Another language…'; // @translate
        // vocabulary-form.js shows each "Another…" choice's text field only
        // when the choice is made.
        $fieldset->add([
            'name' => 'lang',
            'type' => 'select',
            'options' => [
                'label' => 'Preferred language', // @translate
                'info' => 'The language of the labels and comments to use, where the file has them in more than one. A class or property without it takes English where it can.', // @translate
                'value_options' => $languageOptions,
            ],
            'attributes' => [
                'id' => 'lang',
                'data-other-field' => '#other_lang',
            ],
        ]);
        $fieldset->add([
            'name' => 'other_lang',
            'type' => 'text',
            'options' => [
                'label' => 'Language tag', // @translate
                'info' => 'Enter a language the list doesn\'t show, by its tag, such as fr or pt-BR.', // @translate
            ],
            'attributes' => [
                'id' => 'other_lang',
            ],
        ]);
        $fieldset->add([
            'name' => 'labels',
            'type' => 'select',
            'options' => [
                'label' => 'Labels from', // @translate
                'info' => 'Labels come from a class or property\'s preferred label (skos:prefLabel) or label (rdfs:label), else its name or title (foaf:name, rss:title, dc:title, or dc11:title). Choose another property to take them from.', // @translate
                'value_options' => [
                    'label' => 'Preferred label, else label', // @translate
                    'other' => 'Another property…', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'labels',
                'value' => 'label',
                'data-other-field' => '#label_property',
            ],
        ]);
        $fieldset->add([
            'name' => 'label_property',
            'type' => 'text',
            'options' => [
                'label' => 'Label property', // @translate
                'info' => 'The property\'s full URI, in angle brackets, such as <http://www.w3.org/2004/02/skos/core#altLabel>.', // @translate
            ],
            'attributes' => [
                'id' => 'label_property',
            ],
        ]);
        $fieldset->add([
            'name' => 'comments',
            'type' => 'select',
            'options' => [
                'label' => 'Comments from', // @translate
                'info' => 'Comments come from a class or property\'s comment (rdfs:comment) or definition (skos:definition), whichever it has, else its description (dcterms:description or dc11:description). Choose which comes first when it has both, or another property to take them from.', // @translate
                'value_options' => [
                    'comment' => 'Comment, else definition', // @translate
                    'definition' => 'Definition, else comment', // @translate
                    'other' => 'Another property…', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'comments',
                'value' => 'comment',
                'data-other-field' => '#comment_property',
            ],
        ]);
        $fieldset->add([
            'name' => 'comment_property',
            'type' => 'text',
            'options' => [
                'label' => 'Comment property', // @translate
                'info' => 'The property\'s full URI, in angle brackets, such as <http://www.w3.org/2004/02/skos/core#scopeNote>.', // @translate
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

    /**
     * Get the import options the choice fields' values give: only those
     * chosen. Static, as the review page's query and Accept's post give the
     * values as well as the fields do.
     *
     * @param array $values By field name, as CHOICE_FIELDS names them
     * @return array lang, label_property, and comment_property, where chosen
     */
    public static function getChosenImportOptions(array $values)
    {
        $importOptions = [];
        $lang = self::getChosenLanguage($values);
        if (null !== $lang) {
            $importOptions['lang'] = '' === $lang ? null : $lang;
        }
        $labelProperty = self::getChosenLabelProperty($values);
        if (false !== $labelProperty) {
            $importOptions['label_property'] = $labelProperty;
        }
        $commentProperty = self::getChosenCommentProperty($values);
        if (null !== $commentProperty) {
            $importOptions['comment_property'] = $commentProperty;
        }
        return $importOptions;
    }

    /**
     * Get the choice fields' values for import options: the reverse of
     * getChosenImportOptions(). A label or comment property the Labels from
     * or Comments from field doesn't offer was entered by hand, so its box is
     * filled in.
     *
     * @param array $importOptions
     * @return array lang, labels, label_property, comments, and
     *   comment_property
     */
    public static function getChoiceValues(array $importOptions)
    {
        $commentProperty = $importOptions['comment_property'] ?? RdfImporter::COMMENT_PROPERTY;
        $comments = array_search($commentProperty, RdfImporter::COMMENT_PROPERTY_CHOICES, true);
        $labelProperty = $importOptions['label_property'] ?? RdfImporter::LABEL_PROPERTY;
        $isDefaultLabel = RdfImporter::LABEL_PROPERTY === $labelProperty;
        return [
            'lang' => (string) ($importOptions['lang'] ?? ''),
            'labels' => $isDefaultLabel ? 'label' : 'other',
            'label_property' => $isDefaultLabel ? '' : $labelProperty,
            'comments' => false === $comments ? 'other' : $comments,
            'comment_property' => false === $comments ? $commentProperty : '',
        ];
    }

    /**
     * Get the language chosen: one the Preferred language field offers, "" for
     * "First available", or the tag entered under "Another language…".
     * Another with none entered is no choice.
     *
     * @param array $values With "lang" and "other_lang"
     * @return string|null
     */
    protected static function getChosenLanguage(array $values)
    {
        $lang = $values['lang'] ?? null;
        if (!is_string($lang)) {
            return null;
        }
        if ('other' === $lang) {
            $other = is_string($values['other_lang'] ?? null) ? trim($values['other_lang']) : '';
            return '' === $other ? null : $other;
        }
        return $lang;
    }

    /**
     * Get the label property chosen: null for the default the Labels from
     * field offers, or the one entered under "Another property…". Another
     * with none entered is no choice.
     *
     * @param array $values With "labels" and "label_property"
     * @return string|null|false False for no choice
     */
    protected static function getChosenLabelProperty(array $values)
    {
        $labels = $values['labels'] ?? null;
        if ('label' === $labels) {
            return null;
        }
        if ('other' === $labels) {
            $property = is_string($values['label_property'] ?? null) ? trim($values['label_property']) : '';
            return '' === $property ? false : $property;
        }
        return false;
    }

    /**
     * Get the comment property chosen: one the Comments field offers, or the
     * one entered under "Another property…". Another with none entered is no
     * choice.
     *
     * @param array $values With "comments" and "comment_property"
     * @return string|null
     */
    protected static function getChosenCommentProperty(array $values)
    {
        $comments = $values['comments'] ?? null;
        if (!is_string($comments)) {
            return null;
        }
        if ('other' === $comments) {
            $property = is_string($values['comment_property'] ?? null) ? trim($values['comment_property']) : '';
            return '' === $property ? null : $property;
        }
        return RdfImporter::COMMENT_PROPERTY_CHOICES[$comments] ?? null;
    }
}

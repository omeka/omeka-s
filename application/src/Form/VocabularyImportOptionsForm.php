<?php
namespace Omeka\Form;

use Laminas\Form\Form;
use Omeka\Stdlib\RdfImporter;

/**
 * The choices of how a vocabulary file is read: the preferred language, and
 * the properties labels and comments come from. Never validated, so a
 * language the list doesn't offer is read as if entered under Another
 * language… That happens on the review page, whose list is drawn again for
 * the properties chosen: a language picked before may not be in it.
 */
class VocabularyImportOptionsForm extends Form
{
    /**
     * The comment properties the Comments field offers, by option value.
     */
    const COMMENT_PROPERTY_CHOICES = [
        'comment' => RdfImporter::COMMENT_PROPERTY,
        'definition' => RdfImporter::DEFINITION_COMMENT_PROPERTY,
    ];

    /**
     * - languages: (optional) The file's languages, offered between First
     *   available and Another language…
     */
    protected $options = [
        'languages' => [],
    ];

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, array_merge($this->options, $options));
    }

    public function init()
    {
        // No CSRF field, like PageLayoutDataForm: import details post these
        // fields inside VocabularyForm, which has one, and the review page
        // sends them by GET.
        $this->remove($this->getName() . '_csrf');
        $languages = $this->getOption('languages');
        $languageOptions = ['' => 'First available']; // @translate
        foreach ($languages as $tag) {
            $name = extension_loaded('intl') ? \Locale::getDisplayName($tag) : $tag;
            $languageOptions[$tag] = $name === $tag ? $tag : sprintf('%s (%s)', $name, $tag);
        }
        // On import details the list has only the default label and comment
        // properties' languages, so another property's may need entering.
        $languageOptions['other'] = 'Another language…'; // @translate
        // vocabulary-form.js shows each Another… field only while it's chosen.
        $this->add([
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
        $this->add([
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
        $this->add([
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
        $this->add([
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
        $this->add([
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
        $this->add([
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

    /**
     * Set the choices from import options. A language or property the fields
     * don't offer goes under Another…
     *
     * @param array $importOptions lang, label_property, comment_property
     */
    public function setChoices(array $importOptions)
    {
        $choices = [];
        if (array_key_exists('lang', $importOptions)) {
            $lang = (string) ($importOptions['lang'] ?? '');
            if ('' === $lang || in_array($lang, $this->getOption('languages') ?? [], true)) {
                $choices['lang'] = $lang;
            } else {
                $choices['lang'] = 'other';
                $choices['other_lang'] = $lang;
            }
        }
        if (array_key_exists('label_property', $importOptions)) {
            $labelProperty = $importOptions['label_property'] ?? RdfImporter::LABEL_PROPERTY;
            if (RdfImporter::LABEL_PROPERTY === $labelProperty) {
                $choices['labels'] = 'label';
                $choices['label_property'] = '';
            } else {
                $choices['labels'] = 'other';
                $choices['label_property'] = $labelProperty;
            }
        }
        if (array_key_exists('comment_property', $importOptions)) {
            $commentProperty = $importOptions['comment_property'] ?? RdfImporter::COMMENT_PROPERTY;
            $comments = array_search($commentProperty, self::COMMENT_PROPERTY_CHOICES, true);
            if (false === $comments) {
                $choices['comments'] = 'other';
                $choices['comment_property'] = $commentProperty;
            } else {
                $choices['comments'] = $comments;
                $choices['comment_property'] = '';
            }
        }
        $this->populateValues($choices);
    }

    /**
     * Get the import options chosen in the fields.
     *
     * @see self::getChosenImportOptions()
     * @return array lang, label_property, and comment_property, where chosen
     */
    public function getChosen()
    {
        return self::getChosenImportOptions(array_map(fn ($element) => $element->getValue(), $this->getElements()));
    }

    /**
     * Get the import options chosen in the fields' values, leaving out any not
     * chosen. Static, so the review page and Accept can read them from the
     * query without building the form.
     *
     * @param array $values By field name: lang, other_lang, labels,
     *   label_property, comments, and comment_property
     * @return array lang, label_property, and comment_property, where chosen
     */
    public static function getChosenImportOptions(array $values)
    {
        $importOptions = [];
        // Null chooses the default: First available, or the default label
        // property.
        $lang = $values['lang'] ?? null;
        if ('other' === $lang) {
            $lang = self::getOtherValue($values, 'other_lang');
        }
        if (is_string($lang)) {
            $importOptions['lang'] = '' === $lang ? null : $lang;
        }
        $labels = $values['labels'] ?? null;
        if ('label' === $labels) {
            $importOptions['label_property'] = null;
        } elseif ('other' === $labels) {
            $labelProperty = self::getOtherValue($values, 'label_property');
            if (null !== $labelProperty) {
                $importOptions['label_property'] = $labelProperty;
            }
        }
        $comments = $values['comments'] ?? null;
        if ('other' === $comments) {
            $commentProperty = self::getOtherValue($values, 'comment_property');
            if (null !== $commentProperty) {
                $importOptions['comment_property'] = $commentProperty;
            }
        } elseif (is_string($comments) && isset(self::COMMENT_PROPERTY_CHOICES[$comments])) {
            $importOptions['comment_property'] = self::COMMENT_PROPERTY_CHOICES[$comments];
        }
        return $importOptions;
    }

    /**
     * Get the text entered under an Another… choice, or null if none.
     *
     * @param array $values
     * @param string $field
     * @return string|null
     */
    protected static function getOtherValue(array $values, $field)
    {
        $value = is_string($values[$field] ?? null) ? trim($values[$field]) : '';
        return '' === $value ? null : $value;
    }
}

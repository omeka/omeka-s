<?php
namespace Omeka\Form;

use Laminas\Form\Form;
use Omeka\Entity\Vocabulary;

/**
 * A vocabulary's own fields, under the names the API gives them.
 */
class VocabularyForm extends Form
{
    /**
     * - vocabulary: (optional) The vocabulary to edit. Without one, the fields
     *   are a new vocabulary's, prefix included.
     */
    protected $options = [
        'vocabulary' => null,
    ];

    public function __construct($name = null, $options = [])
    {
        parent::__construct($name, array_merge($this->options, $options));
    }

    public function init()
    {
        $vocabulary = $this->getOption('vocabulary');
        $this->setAttribute('id', 'vocabulary-form');
        $this->add([
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
        $this->add([
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
        $this->add([
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
                // vocabulary-form.js skips its "doesn't end with / or #"
                // prompt while the namespace URI is unchanged.
                'data-saved-namespace-uri' => $vocabulary ? $vocabulary->namespaceUri() : null,
            ],
        ]);
        if (!$vocabulary) {
            $this->add([
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

        $inputFilter = $this->getInputFilter();
        // Trimmed, as the API trims it, so a value of only spaces is refused
        // as empty.
        $inputFilter->add([
            'name' => 'o:namespace_uri',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
        ]);
        if (!$vocabulary) {
            $inputFilter->add([
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
    }

    /**
     * Get the namespace URI entered, trimmed as the API trims it.
     *
     * @return string
     */
    public function getNamespaceUri()
    {
        return trim((string) $this->get('o:namespace_uri')->getValue());
    }
}

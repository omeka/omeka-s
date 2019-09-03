<?php
namespace Omeka\Form;

use Zend\Form\Form;

class VocabularyImportForm extends Form
{
    public function init()
    {
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

        $this->add([
            'name' => 'o:namespace_uri',
            'type' => 'text',
            'options' => [
                'label' => 'Namespace URI', // @translate
                'info' => 'The unique namespace URI used to identify the classes and properties of the vocabulary.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:namespace_uri',
            ],
        ]);

        $this->add([
            'name' => 'o:prefix',
            'type' => 'text',
            'options' => [
                'label' => 'Namespace prefix', // @translate
                'info' => 'A concise vocabulary identifier used as a shorthand for the namespace URI.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:prefix',
            ],
        ]);

        $this->add([
            'name' => 'vocabulary-fetch',
            'type' => 'Omeka\Form\Element\VocabularyFetch',
        ]);
    }
}

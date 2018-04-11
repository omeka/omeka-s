<?php
namespace Omeka\Form;

use Zend\Form\Form;

class VocabularyForm extends Form
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
            'name' => 'file',
            'type' => 'file',
            'options' => [
                'label' => 'Update vocabulary', // @translate
                'info' => 'Update this vocabulary to a newer version. You will be able to review the changes before you accept. Accepts the following formats: RDF/XML, RDF/JSON, N-Triples, and Turtle.', // @translate
            ],
            'attributes' => [
                'id' => 'file',
            ],
        ]);
    }
}

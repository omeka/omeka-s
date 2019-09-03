<?php
namespace Omeka\Form\Element;

use Zend\Form\Fieldset;
use Zend\InputFilter\InputFilterProviderInterface;

class VocabularyNamespace extends Fieldset implements InputFilterProviderInterface
{
    public function init()
    {
        $this->setLabel('Namespace'); // @translate

        $this->add([
            'name' => 'o:namespace_uri',
            'type' => 'text',
            'options' => [
                'label' => 'Namespace URI', // @translate
                'info' => 'Enter the unique namespace URI used to identify the classes and properties of the vocabulary.', // @translate
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
                'info' => 'Enter a concise vocabulary identifier used as a shorthand for the namespace URI.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'o:prefix',
            ],
        ]);
    }

    public function getInputFilterSpecification()
    {
        return [];
    }
}

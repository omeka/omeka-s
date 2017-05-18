<?php
namespace Omeka\Form;

use Omeka\Form\Element\ResourceClassSelect;
use Zend\Form\Form;

class ResourceTemplateForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'o:label',
            'type' => 'Text',
            'options' => [
                'label' => 'Label', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:resource_class[o:id]',
            'type' => ResourceClassSelect::class,
            'options' => [
                'label' => 'Suggested Class', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a class',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:label',
            'required' => true,
        ]);
        $inputFilter->add([
            'name' => 'o:resource_class[o:id]',
            'allow_empty' => true,
        ]);
    }
}

<?php
namespace Omeka\Form;

use Omeka\Form\Element\ResourceClassSelect;
use Laminas\Form\Form;

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
                'id' => 'o:label',
            ],
        ]);

        $this->add([
            'name' => 'o:resource_class[o:id]',
            'type' => ResourceClassSelect::class,
            'options' => [
                'label' => 'Suggested class', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a class', // @translate
                'id' => 'o:resource_class[o:id]',
            ],
        ]);

        $this->add([
            'name' => 'o:title_property[o:id]',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'title-property-id',
            ],
        ]);
        $this->add([
            'name' => 'o:description_property[o:id]',
            'type' => 'hidden',
            'attributes' => [
                'id' => 'description-property-id',
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

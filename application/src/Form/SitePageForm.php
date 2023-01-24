<?php
namespace Omeka\Form;

use Laminas\Form\Form;

class SitePageForm extends Form
{
    public function init()
    {
        $this->setAttribute('id', 'site-page-form');

        $this->add([
            'name' => 'o:title',
            'type' => 'Text',
            'options' => [
                'label' => 'Title', // @translate
            ],
            'attributes' => [
                'id' => 'title',
                'required' => true,
            ],
        ]);
        $this->add([
            'name' => 'o:slug',
            'type' => 'Text',
            'options' => [
                'label' => 'URL slug', // @translate
            ],
            'attributes' => [
                'id' => 'slug',
                'required' => false,
            ],
        ]);
        $this->add([
            'name' => 'o:columns',
            'type' => 'Select',
            'options' => [
                'label' => 'Layout', // @translate
                'empty_option'=> 'Normal flow',
                'value_options' => [
                    '1' => 'One column grid', // @translate
                    '2' => 'Two columns grid', // @translate
                    '3' => 'Three columns grid', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'page-columns-select',
                'required' => false,
            ],
        ]);
        if ($this->getOption('addPage')) {
            $this->add([
                'name' => 'add_to_navigation',
                'type' => 'Checkbox',
                'options' => [
                    'label' => 'Add to navigation', // @translate
                ],
            ]);
        }

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:columns',
            'allow_empty' => true,
        ]);
    }
}

<?php
namespace Omeka\Form;

use Zend\Form\Form;

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
        if ($this->getOption('addPage')) {
            $this->add([
                'name' => 'add_to_navigation',
                'type' => 'Checkbox',
                'options' => [
                    'label' => 'Add to navigation', // @translate
                ],
            ]);
        }
    }
}

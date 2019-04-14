<?php
namespace Omeka\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;

class SitePageForm extends Form
{
    public function init()
    {
        $this->setAttribute('id', 'site-page-form');

        $this->add([
            'name' => 'o:title',
            'type' => Element\Text::class,
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
            'type' => Element\Text::class,
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

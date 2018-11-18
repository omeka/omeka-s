<?php
namespace Omeka\Form;

use Zend\Form\Element;
use Zend\Form\Form;

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

        $this->add([
            'name' => 'o:lang',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Language code', // @translate
            ],
            'attributes' => [
                'id' => 'o-lang',
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

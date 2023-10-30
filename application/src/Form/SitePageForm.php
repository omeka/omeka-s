<?php
namespace Omeka\Form;

use Laminas\Form\Form;
use Omeka\Site\Theme\Theme;

class SitePageForm extends Form
{
    protected $currentTheme;

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
        $config = $this->currentTheme->getConfigSpec();
        $valueOptions = [];
        if (isset($config['page_templates']) && is_array($config['page_templates'])) {
            $valueOptions = $config['page_templates'];
        }
        $this->add([
            'type' => 'select',
            'name' => 'template_name',
            'options' => [
                'label' => 'Template',
                'empty_option' => 'Default', // @translate
                'value_options' => $valueOptions,
            ],
            'attributes' => [
                'id' => 'template-name',
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
            'name' => 'template_name',
            'allow_empty' => true,
        ]);
    }

    public function setCurrentTheme(Theme $currentTheme)
    {
        $this->currentTheme = $currentTheme;
    }
}

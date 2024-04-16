<?php
namespace Omeka\Form;

use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;
use Laminas\Form\Form;
use Omeka\Site\Theme\Theme;

class PageLayoutDataForm extends Form
{
    use EventManagerAwareTrait;

    protected $currentTheme;

    public function init()
    {
        // No need for CSRF protection on what is essentially a fieldset.
        $this->remove('csrf');

        $config = $this->currentTheme->getConfigSpec();
        $valueOptions = [];
        if (isset($config['page_templates']) && is_array($config['page_templates'])) {
            $valueOptions = $config['page_templates'];
        }
        $this->add([
            'type' => 'select',
            'name' => 'o:layout_data[template_name]',
            'options' => [
                'label' => 'Template',
                'empty_option' => 'Default', // @translate
                'value_options' => $valueOptions,
            ],
            'attributes' => [
                'id' => 'template-name',
            ],
        ]);
        $this->add([
            'type' => 'number',
            'name' => 'o:layout_data[grid_column_gap]',
            'options' => [
                'label' => 'Column gap (px)',
            ],
            'attributes' => [
                'id' => 'page-layout-grid-column-gap-input',
                'value' => '10',
                'min' => 0,
            ],
        ]);
        $this->add([
            'type' => 'number',
            'name' => 'o:layout_data[grid_row_gap]',
            'options' => [
                'label' => 'Row gap (px)',
            ],
            'attributes' => [
                'id' => 'page-layout-grid-row-gap-input',
                'value' => '10',
                'min' => 0,
            ],
        ]);

        $event = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($event);

        // Note that we don't trigger form.add_input_filters because JS handles
        // validation.
    }

    public function setCurrentTheme(Theme $currentTheme)
    {
        $this->currentTheme = $currentTheme;
    }
}

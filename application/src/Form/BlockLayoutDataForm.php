<?php
namespace Omeka\Form;

use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;
use Laminas\Form\Form;
use Omeka\Site\Theme\Theme;

class BlockLayoutDataForm extends Form
{
    use EventManagerAwareTrait;

    protected $currentTheme;

    public function init()
    {
        $config = $this->currentTheme->getConfigSpec();
        $blockTemplates = [];
        if (isset($config['block_templates']) && is_array($config['block_templates'])) {
            $blockTemplates = $config['block_templates'];
        }
        $valueOptions = [];
        foreach ($blockTemplates as $layoutName => $templates) {
            $valueOptions[$layoutName] = '';
            foreach ($templates as $templateName => $templateLabel) {
                $valueOptions[$layoutName] .= sprintf(
                    '<option value="%s">%s</option>',
                    htmlspecialchars($templateName),
                    htmlspecialchars($templateLabel)
                );
            }
        }
        $this->add([
            'type' => 'select',
            'name' => 'block_template',
            'options' => [
                'label' => 'Template',
                'value_options' => [],
            ],
            'attributes' => [
                'id' => 'block-layout-data-block-template',
                'data-empty-option' => sprintf('<option value="">%s</option>', '[Default]'),
                'data-value-options' => json_encode($valueOptions),
            ],
        ]);
        $this->add([
            'name' => 'class',
            'type' => 'text',
            'options' => [
                'label' => 'Class', // @translate
            ],
            'attributes' => [
                'id' => 'block-layout-data-class',
            ],
        ]);
        $this->add([
            'name' => 'alignment',
            'type' => 'select',
            'options' => [
                'label' => 'Alignment', // @translate
                'empty_option' => 'Default', // @translate
                'value_options' => [
                    'left' => 'Float left', // @translate
                    'right' => 'Float right', // @translate
                    'center' => 'Center', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'block-layout-data-alignment',
            ],
        ]);
        $this->add([
            'name' => 'background-image-asset',
            'type' => \Omeka\Form\Element\Asset::class,
            'options' => [
                'label' => 'Background: image', // @translate
            ],
            'attributes' => [
                'id' => 'block-layout-data-background-image-asset',
            ],
        ]);
        $this->add([
            'name' => 'background-position-y',
            'type' => 'select',
            'options' => [
                'label' => 'Background: image vertical anchor position', // @translate
                'empty_option' => 'Default', // @translate
                'value_options' => [
                    'top' => 'Top', // @translate
                    'center' => 'Center', // @translate
                    'bottom' => 'Bottom', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'block-layout-data-background-position-y',
            ],
        ]);
        $this->add([
            'name' => 'background-position-x',
            'type' => 'select',
            'options' => [
                'label' => 'Background: image horizontal anchor position', // @translate
                'empty_option' => 'Default', // @translate
                'value_options' => [
                    'left' => 'Left', // @translate
                    'center' => 'Center', // @translate
                    'right' => 'Right', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'block-layout-data-background-position-x',
            ],
        ]);

        $event = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($event);

        $inputFilter = $this->getInputFilter();
        $event = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($event);
    }

    public function setCurrentTheme(Theme $currentTheme)
    {
        $this->currentTheme = $currentTheme;
    }
}

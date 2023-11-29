<?php
namespace Omeka\Form;

use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;
use Laminas\Form\Form;
use Laminas\View\HelperPluginManager;
use Omeka\Site\Theme\Theme;

class BlockLayoutDataForm extends Form
{
    use EventManagerAwareTrait;

    protected $currentTheme;

    protected $viewHelpers;

    public function init()
    {
        $escapeHtml = $this->viewHelpers->get('escapeHtml');
        $translate = $this->viewHelpers->get('translate');

        // No need for CSRF protection on what is essentially a fieldset.
        $this->remove('csrf');

        // Get block templates configured by the current theme, if any.
        $config = $this->currentTheme->getConfigSpec();
        $blockTemplates = [];
        if (isset($config['block_templates']) && is_array($config['block_templates'])) {
            $blockTemplates = $config['block_templates'];
        }
        // Build select options for every block layout that has templates.
        $valueOptions = [];
        foreach ($blockTemplates as $layoutName => $templates) {
            $valueOptions[$layoutName] = '';
            foreach ($templates as $templateName => $templateLabel) {
                $valueOptions[$layoutName] .= sprintf(
                    '<option value="%s">%s</option>',
                    $escapeHtml($templateName),
                    $escapeHtml($translate($templateLabel))
                );
            }
        }
        $this->add([
            'type' => 'select',
            'name' => 'template_name',
            'options' => [
                'label' => 'Template',
                'value_options' => [],
            ],
            'attributes' => [
                'id' => 'block-layout-data-template-name',
                'data-block-templates' => json_encode($blockTemplates),
                'data-empty-option' => sprintf('<option value="">%s</option>', $translate('Default')),
                'data-value-options' => json_encode($valueOptions),
                'data-key' => 'template_name',
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
                'data-key' => 'class',
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
                'data-key' => 'alignment',
            ],
        ]);
        $this->add([
            'name' => 'background',
            'type' => 'Omeka\Form\Element\Background',
            'options' => [
                'label' => 'Background',
            ],
        ]);
        $this->add([
            'name' => 'min_height',
            'type' => 'number',
            'options' => [
                'label' => 'Minimum height', // @translate
            ],
            'attributes' => [
                'id' => 'block-layout-data-min-height',
                'data-key' => 'min_height',
                'min' => '0',
            ],
        ]);

        /**
         * Modules can add elements to this fieldset using the form.add_elements
         * event. They can opt-in to automatically populate and apply the values
         * by adding a "data-key" attribute containing the corresponding block
         * layout data key. Elements that need more complex handling must attach
         * to the following JS events on the document:
         *   - o:prepare-block-layout-data
         *   - o:apply-block-layout-data
         */
        $event = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($event);

        // Note that we don't trigger form.add_input_filters because JS handles
        // validation.
    }

    public function setCurrentTheme(Theme $currentTheme)
    {
        $this->currentTheme = $currentTheme;
    }

    public function setViewHelpers(HelperPluginManager $viewHelpers)
    {
        $this->viewHelpers = $viewHelpers;
    }
}

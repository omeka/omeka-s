<?php
namespace Omeka\Form;

use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Form\Form;
use Laminas\View\Helper\Url;
use Omeka\Api\Manager as ApiManager;
use Omeka\Form\Element as OmekaElement;

class ItemStubForm extends Form
{
    use EventManagerAwareTrait;

    protected $viewHelperManager;

    protected $valueIndex = [];

    public function init()
    {
        $urlHelper = $this->viewHelperManager->get('url');
        $apiHelper = $this->viewHelperManager->get('api');

        $this->setAttribute('id', 'item-stub-form');
        $this->setAttribute('data-url', $urlHelper('admin/default', ['controller' => 'item', 'action' => 'add-item-stub']));

        $this->add([
            'type' => OmekaElement\ResourceSelect::class,
            'name' => 'resource_template',
            'options' => [
                'label' => 'Resource template', // @translate
                'empty_option' => '',
                'resource_value_options' => [
                    'resource' => 'resource_templates',
                    'query' => [
                        'sort_by' => 'label',
                    ],
                    'option_text_callback' => function ($resourceTemplate) {
                        return $resourceTemplate->label();
                    },
                ],
            ],
            'attributes' => [
                'id' => 'item-stub-resource-template',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a template', // @translate
            ],
        ]);

        $this->add([
            'type' => OmekaElement\ResourceClassSelect::class,
            'name' => 'resource_class',
            'options' => [
                'label' => 'Class', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'item-stub-resource-class',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a class', // @translate
            ],
        ]);

        $this->add([
            'type' => 'textarea',
            'name' => 'title',
            'options' => [
                'label' => 'Title', // @translate
            ],
            'attributes' => [
                'id' => 'item-stub-title',
                'data-property-term' => 'dcterms:title',
            ],
        ]);

        $this->add([
            'type' => 'textarea',
            'name' => 'description',
            'options' => [
                'label' => 'Description', // @translate
            ],
            'attributes' => [
                'id' => 'item-stub-description',
                'data-property-term' => 'dcterms:description',
            ],
        ]);

        $this->add([
            'type' => 'submit',
            'name' => 'submit',
            'attributes' => [
                'id' => 'item-stub-submit',
                'value' => 'Add and select item', // @translate
            ],
        ]);

        // Allow modules to modify this form. Modules can add value elements by
        // setting a "data-property-term" attribute with the property's term
        // (e.g. dcterms:creator).
        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'resource_template',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->add([
            'name' => 'resource_class',
            'required' => false,
            'allow_empty' => true,
        ]);

        // Allow modules to modify this form's input filters.
        $filterEvent = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($filterEvent);
    }

    public function setViewHelperManager($viewHelperManager)
    {
        $this->viewHelperManager = $viewHelperManager;
    }
}

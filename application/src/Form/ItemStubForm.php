<?php
namespace Omeka\Form;

use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Form\Form;
use Laminas\View\HelperPluginManager;
use Omeka\Api\Manager as ApiManager;
use Omeka\Form\Element as OmekaElement;

class ItemStubForm extends Form
{
    use EventManagerAwareTrait;

    protected $apiManager;

    protected $viewHelperManager;

    public function init()
    {
        $url = $this->getViewHelperManager()->get('url');
        $translate = $this->getViewHelperManager()->get('translate');
        $api = $this->getViewHelperManager()->get('api');

        $this->setAttribute('id', 'item-stub-form');
        $this->setAttribute('data-submit-url', $url(
            'admin/default',
            ['controller' => 'item', 'action' => 'add-item-stub']
        ));
        $this->setAttribute('data-resource-template-url', $url(
            'api/default',
            ['resource' => 'resource_templates']
        ));
        $this->setAttribute('data-property-url', $url(
            'api/default',
            ['resource' => 'properties']
        ));

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

        $property = $api->searchOne(
            'properties',
            ['term' => 'dcterms:title']
        )->getContent();
        $this->add([
            'type' => 'textarea',
            'name' => 'title',
            'options' => [
                'label' => 'Title', // @translate
            ],
            'attributes' => [
                'id' => 'item-stub-title',
                'data-property-id' => $property->id(),
                'data-type' => 'literal',
                'data-property-id-default' => $property->id(),
                'data-property-label-default' => $translate('Title'),
            ],
        ]);

        $property = $api->searchOne(
            'properties',
            ['term' => 'dcterms:description']
        )->getContent();
        $this->add([
            'type' => 'textarea',
            'name' => 'description',
            'options' => [
                'label' => 'Description', // @translate
            ],
            'attributes' => [
                'id' => 'item-stub-description',
                'data-property-id' => $property->id(),
                'data-type' => 'literal',
                'data-property-id-default' => $property->id(),
                'data-property-label-default' => $translate('Description'),
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

        // Allow modules to modify this form.
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

    public function setApiManager(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    public function getApiManager()
    {
        return $this->apiManager;
    }

    public function setViewHelperManager(HelperPluginManager $viewHelperManager)
    {
        $this->viewHelperManager = $viewHelperManager;
    }

    public function getViewHelperManager()
    {
        return $this->viewHelperManager;
    }
}

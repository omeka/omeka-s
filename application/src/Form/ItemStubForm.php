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

    public function init()
    {
        $urlHelper = $this->viewHelperManager->get('url');
        $apiHelper = $this->viewHelperManager->get('api');

        $titleProperty = $apiHelper->searchOne('properties', ['term' => 'dcterms:title'])->getContent();
        $descriptionProperty = $apiHelper->searchOne('properties', ['term' => 'dcterms:description'])->getContent();

        $this->setAttribute('id', 'item-stub-form');
        $this->setAttribute('data-url', $urlHelper('admin/default', ['controller' => 'item', 'action' => 'add-resource-stub']));

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
                'data-property-term' => $titleProperty->term(),
            ],
        ]);
        $this->add([
            'type' => 'textarea',
            'name' => 'description',
            'options' => [
                'label' => 'Description', // @translate
            ],
            'attributes' => [
                'data-property-term' => $descriptionProperty->term(),
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

        // Allow modules to add elements to the item stub form.
        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);
    }

    public function setViewHelperManager($viewHelperManager)
    {
        $this->viewHelperManager = $viewHelperManager;
    }
}

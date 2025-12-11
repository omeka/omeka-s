<?php
namespace Omeka\Form;

use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Form\Element as LaminasElement;
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
        $this->setAttribute('class', 'confirm-container');
        $this->setAttribute('data-submit-url', $url(
            'admin/default',
            ['controller' => 'item', 'action' => 'add-item-stub']
        ));
        $this->setAttribute('data-resource-template-url', $url(
            'api/default',
            ['resource' => 'resource_templates']
        ));
        $titleProperty = $api->searchOne('properties', ['term' => 'dcterms:title'])->getContent();
        $this->setAttribute('data-title-property', json_encode($titleProperty));
        $descriptionProperty = $api->searchOne('properties', ['term' => 'dcterms:description'])->getContent();
        $this->setAttribute('data-description-property', json_encode($descriptionProperty));

        $this->add([
            'type' => 'fieldset',
            'name' => 'fieldset-main',
            'attributes' => [
                'id' => 'item-stub-fieldset-main',
            ],
        ]);
        $fieldsetMain = $this->get('fieldset-main');
        $fieldsetMain->add([
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
        $fieldsetMain->add([
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
        $fieldsetMain->add([
            'type' => LaminasElement\Select::class,
            'name' => 'is_public',
            'options' => [
                'label' => 'Visibility', // @translate
                'value_options' => [
                    '1' => 'Public', // @translate
                    '0' => 'Private', // @translate
                ],
            ],
            'attributes' => [
                'id' => 'item-stub-is-public',
            ],
        ]);
        $this->add([
            'type' => 'fieldset',
            'name' => 'fieldset-property-values',
            'attributes' => [
                'id' => 'item-stub-property-values',
                'class' => 'inputs',
            ],
        ]);

        // Allow modules to modify this form.
        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        $inputFilter = $this->getInputFilter();
        $inputFilter->get('fieldset-main')->add([
            'name' => 'is_public',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->get('fieldset-main')->add([
            'name' => 'resource_template',
            'required' => false,
            'allow_empty' => true,
        ]);
        $inputFilter->get('fieldset-main')->add([
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

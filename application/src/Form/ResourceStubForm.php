<?php
namespace Omeka\Form;

use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Form\Form;
use Laminas\View\Helper\Url;
use Omeka\Form\Element\ResourceSelect;
use Omeka\Form\Element\ResourceClassSelect;

class ResourceStubForm extends Form
{
    use EventManagerAwareTrait;

    protected $urlHelper;

    public function init()
    {
        $urlHelper = $this->getUrlHelper();

        $this->add([
            'type' => ResourceSelect::class,
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
                'id' => 'resource-stub-resource-template-select',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a template', // @translate
                'data-api-base-url' => $urlHelper('api/default', ['resource' => 'resource_templates']),
            ],
        ]);
        $this->add([
            'type' => ResourceClassSelect::class,
            'name' => 'resource_class',
            'options' => [
                'label' => 'Class', // @translate
                'empty_option' => '',
            ],
            'attributes' => [
                'id' => 'resource-stub-resource-class-select',
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
                'id' => 'resource-stub-title-textarea',
            ],
        ]);
        $this->add([
            'type' => 'textarea',
            'name' => 'description',
            'options' => [
                'label' => 'Description', // @translate
            ],
            'attributes' => [
                'id' => 'resource-stub-description-textarea',
            ],
        ]);
    }

    public function setUrlHelper(Url $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    public function getUrlHelper()
    {
        return $this->urlHelper;
    }
}

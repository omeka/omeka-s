<?php
namespace Omeka\Form;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Form\Element\ResourceSelect;
use Omeka\Form\Element\ResourceClassSelect;
use Laminas\Form\Form;
use Laminas\View\Helper\Url;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\EventManager\Event;

class ResourceForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * @var Url
     */
    protected $urlHelper;

    /**
     * @var AbstractResourceEntityRepresentation|null
     */
    protected $resource;

    public function init()
    {
        $urlHelper = $this->getUrlHelper();
        $resource = $this->getResource();

        $this->setAttribute('class', 'resource-form');
        $this->add([
            'name' => 'o:resource_template[o:id]',
            'type' => ResourceSelect::class,
            'attributes' => [
                'id' => 'resource-template-select',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a template', // @translate
                'data-api-base-url' => $urlHelper('api/default', ['resource' => 'resource_templates']),
            ],
            'options' => [
                'label' => 'Resource template', // @translate
                'info' => 'A pre-defined template for resource creation.', // @translate
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
        ]);

        $this->add([
            'name' => 'o:resource_class[o:id]',
            'type' => ResourceClassSelect::class,
            'attributes' => [
                'id' => 'resource-class-select',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a class', // @translate
            ],
            'options' => [
                'label' => 'Class', // @translate
                'info' => 'A type for the resource. Different types have different default properties attached to them.', // @translate
                'empty_option' => '',
            ],
        ]);

        $this->add([
            'name' => 'o:thumbnail[o:id]',
            'type' => 'Omeka\Form\Element\Asset',
            'options' => [
                'label' => 'Thumbnail', // @translate
                'info' => 'Omeka S automatically selects a thumbnail from among attached media for a resource. You may use an image of your choice instead by choosing an asset here.', // @translate
            ],
        ]);

        $owner = $resource ? $resource->owner() : null;
        $this->add([
            'name' => 'o:owner[o:id]',
            'type' => ResourceSelect::class,
            'attributes' => [
                'id' => 'resource-owner-select',
                'class' => 'chosen-select',
                'data-api-base-url' => $urlHelper('api/default', ['resource' => 'users']),
            ],
            'options' => [
                'label' => 'Owner', // @translate
                'empty_option' => $owner ? null : '[No owner]', // @translate
                'resource_value_options' => [
                    'resource' => 'users',
                    'query' => [],
                    'option_text_callback' => function ($user) {
                        return $user->name();
                    },
                ],
            ],
        ]);

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'o:resource_template[o:id]',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'o:resource_class[o:id]',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'o:owner[o:id]',
            'required' => false,
        ]);

        $filterEvent = new Event('form.add_input_filters', $this, ['inputFilter' => $inputFilter]);
        $this->getEventManager()->triggerEvent($filterEvent);
    }

    /**
     * @param Url $urlHelper
     */
    public function setUrlHelper(Url $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    /**
     * @return Url
     */
    public function getUrlHelper()
    {
        return $this->urlHelper;
    }

    /**
     * @param AbstractResourceEntityRepresentation $resource
     */
    public function setResource(?AbstractResourceEntityRepresentation $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return ?AbstractResourceEntityRepresentation
     */
    public function getResource()
    {
        return $this->resource;
    }
}

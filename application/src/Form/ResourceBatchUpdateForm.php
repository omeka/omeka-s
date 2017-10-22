<?php
namespace Omeka\Form;

use Omeka\Form\Element\ItemSetSelect;
use Omeka\Form\Element\PropertySelect;
use Omeka\Form\Element\ResourceClassSelect;
use Omeka\Form\Element\ResourceSelect;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Form\Form;
use Zend\View\Helper\Url;

class ResourceBatchUpdateForm extends Form
{
    use EventManagerAwareTrait;

    /**
     * @var Url
     */
    protected $urlHelper;

    public function init()
    {
        $urlHelper = $this->getUrlHelper();

        $resourceType = $this->getOption('resource_type');

        $this->add([
            'name' => 'is_public',
            'type' => 'radio',
            'options' => [
                'label' => 'Set visibility', // @translate
                'value_options' => [
                    '' => '[No change]', // @translate
                    '1' => 'Public', // @translate
                    '0' => 'Not public', // @translate
                ],
            ],
        ]);

        if ($resourceType === 'itemSet') {
            $this->add([
                'name' => 'is_open',
                'type' => 'radio',
                'options' => [
                    'label' => 'Set openness', // @translate
                    'value_options' => [
                        '' => '[No change]', // @translate
                        '1' => 'Open', // @translate
                        '0' => 'Not open', // @translate
                    ],
                ],
            ]);
        }

        $this->add([
            'name' => 'resource_template',
            'type' => ResourceSelect::class,
            'attributes' => [
                'id' => 'resource-template-select',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a template', // @translate
                'data-api-base-url' => $urlHelper('api/default', ['resource' => 'resource_templates']),
            ],
            'options' => [
                'label' => 'Set template', // @translate
                'empty_option' => '[No change]', // @translate
                'prepend_value_options' => ['-1' => '[Unset template]'], // @translate
                'resource_value_options' => [
                    'resource' => 'resource_templates',
                    'query' => [],
                    'option_text_callback' => function ($resourceTemplate) {
                        return $resourceTemplate->label();
                    },
                ],
            ],
        ]);

        $this->add([
            'name' => 'resource_class',
            'type' => ResourceClassSelect::class,
            'attributes' => [
                'id' => 'resource-class-select',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select a class', // @translate
            ],
            'options' => [
                'label' => 'Set class', // @translate
                'prepend_value_options' => ['-1' => '[Unset class]'], // @translate
                'empty_option' => '[No change]', // @translate
            ],
        ]);

        if ($resourceType === 'item') {
            $this->add([
                'name' => 'add_to_item_set',
                'type' => ItemSetSelect::class,
                'attributes' => [
                    'id' => 'add-to-item-sets',
                    'class' => 'chosen-select',
                    'multiple' => true,
                    'data-placeholder' => 'Select item sets', // @translate
                ],
                'options' => [
                    'label' => 'Add to item sets', // @translate
                ],
            ]);

            $this->add([
                'name' => 'remove_from_item_set',
                'type' => ItemSetSelect::class,
                'attributes' => [
                    'id' => 'remove-from-item-sets',
                    'class' => 'chosen-select',
                    'multiple' => true,
                    'data-placeholder' => 'Select item sets', // @translate
                ],
                'options' => [
                    'label' => 'Remove from item sets', // @translate
                ],
            ]);
        }

        $this->add([
            'name' => 'clear_property_values',
            'type' => PropertySelect::class,
            'attributes' => [
                'id' => 'remove-property-values',
                'class' => 'chosen-select',
                'multiple' => true,
                'data-placeholder' => 'Select properties', // @translate
            ],
            'options' => [
                'label' => 'Clear property values', // @translate
            ],
        ]);

        // TODO Add dynamic properties here instead of the partial.

        $addEvent = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($addEvent);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'is_public',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'is_open',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'resource_template',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'resource_class',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'add_to_item_set',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'remove_from_item_set',
            'required' => false,
        ]);
        $inputFilter->add([
            'name' => 'clear_property_values',
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
}

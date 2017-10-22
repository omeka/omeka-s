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

        // This hidden element manages the elements "value" added in the view.
        $this->add([
            'name' => 'value',
            'type' => 'Hidden',
            'attributes' => [
                'value' => '',
            ],
        ]);

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
        $inputFilter->add([
            'name' => 'value',
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
     * Preprocess data in order to get data to apply, to remove and to append.
     *
     * Batch update data contains instructions on what to update. It needs to be
     * preprocessed before it's sent to the API.
     *
     * @todo Use standard validationGroup and filters.
     * Note: The elements added by modules are added according to the attribute
     * "data-preprocess-group", that can be "remove" (default) or "append". The
     * attribute "data-preprocess-key" saves the key when needed. It avoids to
     * attach an event. Anyway, the triggers "api.batch_update.{pre|post}" and
     * some other ones can be used.
     *
     * @return array An array containing the collectionAction=remove data as the
     * first element and the collectionAction=append data as the second.
     */
    public function preprocessData()
    {
        $data = $this->getData();

        $dataRemove = [];
        $dataAppend = [];

        // Set the data to change and data to remove.
        if (array_key_exists('is_public', $data) && in_array($data['is_public'], ['0', '1'])) {
            $dataRemove['o:is_public'] = $data['is_public'];
        }
        if (array_key_exists('is_open', $data) && in_array($data['is_open'], ['0', '1'])) {
            $dataRemove['o:is_open'] = $data['is_open'];
        }
        if (-1 == $data['resource_template']) {
            $dataRemove['o:resource_template'] = ['o:id' => null];
        } elseif (is_numeric($data['resource_template'])) {
            $dataRemove['o:resource_template'] = ['o:id' => $data['resource_template']];
        }
        if (-1 == $data['resource_class']) {
            $dataRemove['o:resource_class'] = ['o:id' => null];
        } elseif (is_numeric($data['resource_class'])) {
            $dataRemove['o:resource_class'] = ['o:id' => $data['resource_class']];
        }
        if (isset($data['remove_from_item_set'])) {
            $dataRemove['o:item_set'] = $data['remove_from_item_set'];
        }
        if (isset($data['clear_property_values'])) {
            $dataRemove['clear_property_values'] = $data['clear_property_values'];
        }

        // Set the data to append.
        if (!empty($data['value'])) {
            foreach ($data['value'] as $value) {
                $valueObj = [
                    'property_id' => $value['property_id'],
                    'type' => $value['type'],
                ];
                switch ($value['type']) {
                    case 'uri':
                        $valueObj['@id'] = $value['id'];
                        $valueObj['o:label'] = $value['label'];
                        break;
                    case 'resource':
                        $valueObj['value_resource_id'] = $value['value_resource_id'];
                        break;
                    case 'literal':
                    default:
                        $valueObj['@value'] = $value['value'];
                }
                $dataAppend[$value['property_id']][] = $valueObj;
            }
        }
        if (isset($data['add_to_item_set'])) {
            $dataAppend['o:item_set'] = array_unique($data['add_to_item_set']);
        }

        // Set remaining elements according to attribute data-preprocess-group.
        $processeds = [
            'csrf', 'is_public', 'is_open', 'resource_template', 'resource_class',
            'remove_from_item_set', 'add_to_item_set', 'clear_property_values',
            'value', 'id', 'o:id',
        ];
        foreach ($data as $key => $value) {
            if (is_numeric($key) || in_array($key, $processeds)
                || is_null($value) || $value === ''
            ) {
                continue;
            }
            if ($this->has($key)) {
                $element = $this->get($key);
                $elementKey = $element->getAttribute('data-preprocess-key') ?: $key;
                if ($element->getAttribute('data-preprocess-group') == 'append') {
                    $dataAppend[$elementKey] = $value;
                } else {
                    $dataRemove[$elementKey] = $value;
                }
            } else {
                $dataRemove[$key] = $value;
            }
        }

        return [$dataRemove, $dataAppend];
    }
}

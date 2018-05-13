<?php
namespace Omeka\Form;

use Omeka\Form\Element\ItemSetSelect;
use Omeka\Form\Element\PropertySelect;
use Omeka\Form\Element\ResourceClassSelect;
use Omeka\Form\Element\ResourceSelect;
use Zend\EventManager\Event;
use Zend\EventManager\EventManagerAwareTrait;
use Zend\Form\Element;
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
            'type' => Element\Radio::class,
            'options' => [
                'label' => 'Set visibility', // @translate
                'value_options' => [
                    '1' => 'Public', // @translate
                    '0' => 'Not public', // @translate
                    '' => '[No change]', // @translate
                ],
            ],
            'attributes' => [
                'value' => '',
            ],
        ]);

        if ($resourceType === 'itemSet') {
            $this->add([
                'name' => 'is_open',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Set openness', // @translate
                    'value_options' => [
                        '1' => 'Open', // @translate
                        '0' => 'Not open', // @translate
                        '' => '[No change]', // @translate
                    ],
                ],
                'attributes' => [
                    'value' => '',
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

        switch ($resourceType) {
            case 'item':
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
                break;

            case 'media':
                $this->add([
                    'name' => 'clear_language',
                    'type' => Element\Checkbox::class,
                    'options' => [
                        'label' => 'Clear language', // @translate
                    ],
                ]);

                $this->add([
                    'name' => 'language',
                    'type' => Element\Text::class,
                    'attributes' => [
                        'class' => 'value-language active',
                    ],
                    'options' => [
                        'label' => 'Set language', // @translate
                    ],
                ]);
                break;
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
            'type' => Element\Hidden::class,
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
     * Preprocess data to get data to replace, to remove and to append.
     *
     * Batch update data contains instructions on what to update. It needs to be
     * preprocessed before it's sent to the API. The elements are udpated by
     * entity according to the attribute "data-collection-action", that can be
     * "replace" (default), "remove" or "append".
     *
     * @todo Use standard validationGroup and filters.
     *
     * @return array Associative array of data to replace, to remove and to
     * append.
     */
    public function preprocessData()
    {
        $data = $this->getData();
        $preData = [
            'replace' => null,
            'remove' => null,
            'append' => null,
        ];

        // Set the data to change and data to remove.
        if (array_key_exists('is_public', $data) && in_array($data['is_public'], ['0', '1'])) {
            $preData['remove']['o:is_public'] = $data['is_public'];
        }
        if (array_key_exists('is_open', $data) && in_array($data['is_open'], ['0', '1'])) {
            $preData['remove']['o:is_open'] = $data['is_open'];
        }
        if (-1 == $data['resource_template']) {
            $preData['remove']['o:resource_template'] = ['o:id' => null];
        } elseif (is_numeric($data['resource_template'])) {
            $preData['remove']['o:resource_template'] = ['o:id' => $data['resource_template']];
        }
        if (-1 == $data['resource_class']) {
            $preData['remove']['o:resource_class'] = ['o:id' => null];
        } elseif (is_numeric($data['resource_class'])) {
            $preData['remove']['o:resource_class'] = ['o:id' => $data['resource_class']];
        }
        if (isset($data['remove_from_item_set'])) {
            $preData['remove']['o:item_set'] = $data['remove_from_item_set'];
        }
        if (isset($data['clear_property_values'])) {
            $preData['remove']['clear_property_values'] = $data['clear_property_values'];
        }
        if (!empty($data['clear_language'])) {
            $preData['remove']['o:lang'] = null;
        }
        if (!empty($data['language'])) {
            $preData['remove']['o:lang'] = $data['language'];
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
                $preData['append'][$value['property_id']][] = $valueObj;
            }
        }
        if (isset($data['add_to_item_set'])) {
            $preData['append']['o:item_set'] = array_unique($data['add_to_item_set']);
        }

        // Set remaining elements according to attribute data-collection-action.
        $processeds = [
            'is_public', 'is_open', 'resource_template', 'resource_class',
            'remove_from_item_set', 'add_to_item_set',
            'clear_property_values', 'value',
            'clear_language', 'language',
            'csrf', 'id', 'o:id',
        ];

        foreach ($data as $key => $value) {
            if (is_numeric($key) || in_array($key, $processeds)
                || is_null($value) || $value === ''
            ) {
                continue;
            }
            $collectionAction = $this->has($key)
                ? $this->get($key)->getAttribute('data-collection-action')
                : 'replace';
            $preData[$collectionAction][$key] = $value;
        }

        return array_filter($preData);
    }
}

<?php
namespace Omeka\Form;

use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerAwareTrait;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Omeka\DataType\Manager as DataTypeManager;

class ResourceTemplatePropertyFieldset extends Fieldset implements InputFilterProviderInterface
{
    use EventManagerAwareTrait;

    /**
     * @var DataTypeManager
     */
    protected $dataTypeManager;

    public function init()
    {
        // Fieldset displayed in the sidebar of the resource template form and
        // as hidden collection in main part. Values are copied hiddenly in main
        // part form via js.
        // The ids are duplicated when there are multiple rows, so they are
        // managed in the view.
        $this
            ->setLabel('Property') // @translate
            ->add([
                'name' => 'o:property[o:id]',
                'type' => Element\Hidden::class,
                'attributes' => [
                    // 'id' => 'property-id',
                    'data-property-key' => 'o:property[o:id]',
                ],
            ])
            ->add([
                'name' => 'o:alternate_label',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Alternate', // @translate
                ],
                'attributes' => [
                    // 'id' => 'alternate-label',
                    'data-property-key' => 'o:alternate_label',
                ],
            ])
            ->add([
                'name' => 'o:alternate_comment',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Alternate', // @translate
                ],
                'attributes' => [
                    // 'id' => 'alternate-comment',
                    'data-property-key' => 'o:alternate_comment',
                ],
            ])
            // This value is a template parameter and managed via js.
            ->add([
                'name' => 'is-title-property',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Use for resource title', // @translate
                ],
                'attributes' => [
                    // 'id' => 'is-title-property',
                    'data-property-key' => 'is-title-property',
                ],
            ])
            // This value is a template parameter and managed via js.
            ->add([
                'name' => 'is-description-property',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Use for resource description', // @translate
                ],
                'attributes' => [
                    // 'id' => 'is-description-property',
                    'data-property-key' => 'is-description-property',
                ],
            ])
            ->add([
                'name' => 'o:is_required',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Required', // @translate
                ],
                'attributes' => [
                    // 'id' => 'is-required',
                    'data-property-key' => 'o:is_required',
                ],
            ])
            ->add([
                'name' => 'o:is_private',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Private', // @translate
                ],
                'attributes' => [
                    // 'id' => 'is-private',
                    'data-property-key' => 'o:is_private',
                ],
            ])
            ->add([
                'name' => 'o:data_type',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Data type', // @translate
                    'value_options' => $this->listDataTypesForSelect(),
                    'empty_option' => 'Default', // @translate
                ],
                'attributes' => [
                    // 'id' => 'data-type',
                    'multiple' => false,
                    'data-placeholder' => 'Select data type…', // @translate
                    'data-property-key' => 'o:data_type',
                ],
            ]);

        $event = new Event('form.add_elements', $this);
        $this->getEventManager()->triggerEvent($event);
    }

    /**
     * This method is required when a fieldset is used as a collection, else the
     * data are not returned with getData().
     *
     * {@inheritDoc}
     * @see \Laminas\InputFilter\InputFilterProviderInterface::getInputFilterSpecification()
     */
    public function getInputFilterSpecification()
    {
        // Remove required option for attached settings.
        $spec = [
            'o:data_type' => [
                'required' => false,
            ],
            'o:data' => [
                'required' => false,
            ],
        ];
        foreach ($this->getElements() as $element) {
            if ($element->getAttribute('data-setting-key')) {
                $spec[$element->getName()] = [
                    'required' => false,
                    'allow_empty' => true,
                ];
            }
        }
        return $spec;
    }

    /**
     * List datatypes for options.
     *
     * @see \Omeka\View\Helper\DataType::getSelect()
     *
     * @return array
     */
    protected function listDataTypesForSelect()
    {
        $options = [];
        $optgroupOptions = [];
        foreach ($this->dataTypeManager->getRegisteredNames() as $dataTypeName) {
            $dataType = $this->dataTypeManager->get($dataTypeName);
            $label = $dataType->getLabel();
            if ($optgroupLabel = $dataType->getOptgroupLabel()) {
                // Hash the optgroup key to avoid collisions when merging with
                // data types without an optgroup.
                $optgroupKey = md5($optgroupLabel);
                // Put resource data types before ones added by modules.
                $optionsVal = in_array($dataTypeName, ['resource', 'resource:item', 'resource:itemset', 'resource:media'])
                    ? 'options'
                    : 'optgroupOptions';
                if (!isset(${$optionsVal}[$optgroupKey])) {
                    ${$optionsVal}[$optgroupKey] = [
                        'label' => $optgroupLabel,
                        'options' => [],
                    ];
                }
                ${$optionsVal}[$optgroupKey]['options'][$dataTypeName] = $label;
            } else {
                $options[$dataTypeName] = $label;
            }
        }
        // Always put data types not organized in option groups before data
        // types organized within option groups.
        return array_merge($options, $optgroupOptions);
    }

    public function setDataTypeManager(DataTypeManager $dataTypeManager)
    {
        $this->dataTypeManager = $dataTypeManager;
    }
}

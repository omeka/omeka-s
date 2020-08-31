<?php
namespace Omeka\Form\Element;

use Omeka\DataType\Manager as DataTypeManager;
use Zend\Form\Element\Select;

class DataTypeSelect extends Select
{
    protected $attributes = [
        'type' => 'select',
        'multiple' => false,
        'class' => 'chosen-select',
    ];

    /**
     * @var DataTypeManager
     */
    protected $dataTypeManager;

    /**
     * @var array
     */
    protected $dataTypes = [];

    public function getValueOptions()
    {
        $options = [];
        $optgroupOptions = [];
        foreach ($this->dataTypes as $dataTypeName) {
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

    /**
     * @param DataTypeManager $dataTypeManager
     * @return self
     */
    public function setDataTypeManager(DataTypeManager $dataTypeManager)
    {
        $this->dataTypeManager = $dataTypeManager;
        $this->dataTypes = $dataTypeManager->getRegisteredNames();
        return $this;
    }

    /**
     * @return DataTypeManager
     */
    public function getDataTypeManager()
    {
        return $this->dataTypeManager;
    }
}

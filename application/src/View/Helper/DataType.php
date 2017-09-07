<?php
namespace Omeka\View\Helper;

use Omeka\DataType\Manager as DataTypeManager;
use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

/**
 * View helper for rendering data types.
 */
class DataType extends AbstractHelper
{
    /**
     * @var DataTypeManager
     */
    protected $manager;

    protected $dataTypes;

    /**
     * Construct the helper.
     *
     * @param DataTypeManager $dataTypeManager
     */
    public function __construct(DataTypeManager $dataTypeManager)
    {
        $this->manager = $dataTypeManager;
        $this->dataTypes = $this->manager->getRegisteredNames();
    }

    /**
     * Get the data type select markup.
     *
     * By default, options are listed in this order:
     *
     *   - Native data types (literal, uri, resource)
     *   - Data types not organized in option groups
     *   - Data types organized in option groups
     *
     * @param string $name
     * @param string $value
     * @param array $attributes
     */
    public function getSelect($name, $value = null, $attributes = [])
    {
        $options = [];
        $optgroupOptions = [];
        foreach ($this->dataTypes as $dataTypeName) {
            $dataType = $this->manager->get($dataTypeName);
            $label = $dataType->getLabel();
            if ($optgroupLabel = $dataType->getOptgroupLabel()) {
                // Hash the optgroup key to avoid collisions when merging with
                // data types without an optgroup.
                $optgroupKey = md5($optgroupLabel);
                // Put resource data types before ones added by modules.
                $optionsVal = in_array($dataTypeName, ['resource', 'resource:item', 'resource:itemset', 'resource:media'])
                    ? 'options' : 'optgroupOptions';
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
        $options = array_merge($options, $optgroupOptions);

        $element = new Select($name);
        $element->setEmptyOption('Default')
            ->setValueOptions($options)
            ->setAttributes($attributes);
        $element->setValue($value);
        return $this->getView()->formSelect($element);
    }

    public function getTemplates()
    {
        $view = $this->getView();
        $templates = '';
        foreach ($this->dataTypes as $dataType) {
            $templates .= $view->partial('common/data-type-wrapper', [
                'dataType' => $dataType,
                'resource' => isset($view->resource) ? $view->resource : null,
            ]);
        }
        return $templates;
    }

    public function getTemplate($dataType)
    {
        return $this->manager->get($dataType)->form($this->getView());
    }

    /**
     * Prepare the view to enable the data types.
     */
    public function prepareForm()
    {
        foreach ($this->dataTypes as $dataType) {
            $this->manager->get($dataType)->prepareForm($this->getView());
        }
    }
}

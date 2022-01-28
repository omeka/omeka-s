<?php
namespace Omeka\View\Helper;

use Omeka\DataType\Manager as DataTypeManager;
use Omeka\DataType\ValueAnnotatingInterface;
use Laminas\Form\Element\Select;
use Laminas\View\Helper\AbstractHelper;

/**
 * View helper for rendering data types.
 */
class DataType extends AbstractHelper
{
    /**
     * @var DataTypeManager
     */
    protected $manager;

    protected $valueAnnotatingDataTypes;

    protected $dataTypes;

    /**
     * Construct the helper.
     *
     * @param DataTypeManager $dataTypeManager
     */
    public function __construct(DataTypeManager $dataTypeManager, array $valueAnnotatingDataTypes)
    {
        $this->manager = $dataTypeManager;
        $this->valueAnnotatingDataTypes = $valueAnnotatingDataTypes;
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
     * @param string|array $value
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
        $element->setEmptyOption('')
            ->setValueOptions($options)
            ->setAttributes($attributes);
        if (!$element->getAttribute('multiple') && is_array($value)) {
            $value = reset($value);
        }
        $element->setValue($value);
        return $this->getView()->formSelect($element);
    }

    public function getTemplates()
    {
        $view = $this->getView();
        $templates = '';
        $resource = isset($view->resource) ? $view->resource : null;
        $partial = $view->plugin('partial');
        foreach ($this->dataTypes as $dataType) {
            $templates .= $partial('common/data-type-wrapper', [
                'dataType' => $dataType,
                'resource' => $resource,
            ]);
        }
        return $templates;
    }

    public function getTemplate($dataType)
    {
        return $this->manager->get($dataType)->form($this->getView());
    }

    public function getLabel($dataType)
    {
        return $this->manager->get($dataType)->getLabel();
    }

    /**
     * @param string $dataType
     * @return \Omeka\DataType\DataTypeInterface|null
     */
    public function getDataType($dataType)
    {
        return $this->manager->has($dataType)
            ? $this->manager->get($dataType)
            : null;
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

    public function getValueAnnotationDataTypes()
    {
        $dataTypes = [];
        foreach ($this->valueAnnotatingDataTypes as $dataTypeName) {
            $dataType = $this->manager->get($dataTypeName);
            if ($dataType instanceof ValueAnnotatingInterface) {
                $dataTypes[$dataTypeName] = $dataType;
            }
        }
        return $dataTypes;
    }

    public function getValueAnnotationSelect($name, $attributes = [])
    {
        $options = [];
        $optgroupOptions = [];
        foreach ($this->getValueAnnotationDataTypes() as $dataTypeName => $dataType) {
            $label = $dataType->getLabel();
            if ($optgroupLabel = $dataType->getOptgroupLabel()) {
                $optgroupKey = md5($optgroupLabel);
                $optionsVal = in_array($dataTypeName, ['resource:item', 'resource:itemset', 'resource:media']) ? 'options' : 'optgroupOptions';
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
        $element = new Select($name);
        $element->setValueOptions(array_merge($options, $optgroupOptions))
            ->setAttributes($attributes);
        return $this->getView()->formSelect($element);
    }

    public function getValueAnnotationTemplates()
    {
        $view = $this->getView();
        $templates = [];
        foreach ($this->getValueAnnotationDataTypes() as $dataTypeName => $dataType) {
            $template = $view->partial('common/value-annotation-wrapper', ['dataType' => $dataType]);
            $templates[$dataTypeName] = $template;
        }
        return $templates;
    }
}

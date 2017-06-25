<?php
namespace Omeka\View\Helper;

use Omeka\DataType\Manager as DataTypeManager;
use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

class DataType extends AbstractHelper
{
    /**
     * @var DataTypeManager
     */
    protected $manager;

    protected $dataTypes;

    public function __construct(DataTypeManager $dataTypeManager)
    {
        $this->manager = $dataTypeManager;
        $this->dataTypes = $this->manager->getRegisteredNames();
    }

    /**
     * Get the data type select markup.
     *
     * @param string $name
     * @param string $value
     */
    public function getSelect($name, $value, $attributes = [])
    {
        $valueOptions = [];
        foreach ($this->dataTypes as $dataTypeName) {
            $dataType = $this->manager->get($dataTypeName);
            $valueOption = [
                'value' => $dataTypeName,
                'label' => $dataType->getLabel(),
                'attributes' => [],
            ];
            $optionsFormUrl = $dataType->getOptionsFormUrl($this->getView());
            if ($optionsFormUrl) {
                $valueOption['attributes'] = [
                    'data-options-form-url' => $optionsFormUrl,
                ];
            }
            $valueOptions[] = $valueOption;
        }

        $element = new Select($name);
        $element->setEmptyOption('Default')
            ->setValueOptions($valueOptions)
            ->setAttributes($attributes);
        if (!array_key_exists($value, $valueOptions)) {
            $value = null;
        }
        $element->setValue($value);
        return $this->getView()->formSelect($element);
    }

    public function getTemplates()
    {
        $templates = '';
        foreach ($this->dataTypes as $dataType) {
            $templates .= $this->getView()->partial('common/data-type-wrapper', [
                'dataType' => $dataType,
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

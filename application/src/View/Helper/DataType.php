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
            $valueOptions[$dataTypeName] = $this->manager->get($dataTypeName)->getLabel();
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

    public function getOptionsForms()
    {
        $optionsForms = [];
        foreach ($this->dataTypes as $dataTypeName) {
            $optionsForm = $this->manager->get($dataTypeName)->optionsForm($this->getView());
            if ($optionsForm) {
                $optionsForms[$dataTypeName] = $optionsForm;
            }
        }
        return $optionsForms;
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
     * Prepare the view to enable the data type options.
     */
    public function prepareOptionsForm()
    {
        foreach ($this->dataTypes as $dataType) {
            $this->manager->get($dataType)->prepareOptionsForm($this->getView());
        }
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

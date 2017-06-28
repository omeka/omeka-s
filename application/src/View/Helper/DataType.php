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

    protected $valueOptions = [];

    public function __construct(DataTypeManager $dataTypeManager)
    {
        $this->manager = $dataTypeManager;
        $this->dataTypes = $this->manager->getRegisteredNames();
        foreach ($this->dataTypes as $dataType) {
            $this->valueOptions[$dataType] = $this->manager->get($dataType)->getLabel();
        }
    }

    /**
     * Get the data type select markup.
     *
     * @param string $name
     * @param string $value
     */
    public function getSelect($name, $value, $attributes = [])
    {
        $element = new Select($name);
        $element->setEmptyOption('Default')
            ->setValueOptions($this->valueOptions)
            ->setAttributes($attributes);
        if (!array_key_exists($value, $this->valueOptions)) {
            $value = null;
        }
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

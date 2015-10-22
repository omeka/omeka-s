<?php
namespace Omeka\View\Helper;

use Zend\Form\Element\Select;
use Zend\View\Helper\AbstractHelper;

abstract class AbstractSelect extends AbstractHelper
{
    /**
     * @var array
     */
    protected $valueOptions = [];

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var string
     */
    protected $emptyOption = '';

    /**
     * @var string
     */
    protected $value;

    /**
     * Get the select value options.
     *
     * @return array
     */
    abstract public function getValueOptions();

    /**
     * @return self
     */
    public function __invoke() {
        if (!$this->valueOptions) {
            $this->valueOptions = $this->getValueOptions();
        }
        return $this;
    }

    /**
     * @param array $attributes
     * @return self
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @param string $emptyOption
     * @return self
     */
    public function setEmptyOption($emptyOption)
    {
        $this->emptyOption = $emptyOption;
        return $this;
    }

    /**
     * @param mixed $value
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Render the select element.
     *
     * @param string $name
     * @return string
     */
    public function render($name)
    {
        $select = new Select;
        $select->setName($name)
            ->setValueOptions($this->valueOptions)
            ->setAttributes($this->attributes)
            ->setEmptyOption($this->emptyOption)
            ->setValue($this->value);
        return $this->getView()->formSelect($select);
    }
}

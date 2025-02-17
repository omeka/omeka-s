<?php
namespace NumericDataTypes\Form\Element;

use Omeka\Form\Element\PropertySelect;
use Laminas\Form\Element;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ConvertToNumeric extends Element
{
    protected $formElements;
    protected $propertyElement;
    protected $typeElement;

    public function setFormElementManager(ServiceLocatorInterface  $formElements)
    {
        $this->formElements = $formElements;
    }

    public function init()
    {
        $this->setAttribute('data-collection-action', 'replace');
        $this->setLabel('Convert to numeric'); // @translate
        $this->propertyElement = $this->formElements->get(PropertySelect::class)
            ->setName('numeric_convert[property]')
            ->setEmptyOption('Select property') // @translate
            ->setAttributes([
                'class' => 'chosen-select',
                'data-placeholder' => 'Select property', // @translate
            ]);
        $this->typeElement = (new Element\Select('numeric_convert[type]'))
            ->setEmptyOption('[No change]') // @translate
            ->setValueOptions([
                'numeric:timestamp' => 'Convert to timestamp', // @translate
                'numeric:interval' => 'Convert to interval', // @translate
                'numeric:duration' => 'Convert to duration', // @translate
                'numeric:integer' => 'Convert to integer', // @translate
            ]);
    }

    public function getPropertyElement()
    {
        return $this->propertyElement;
    }

    public function getTypeElement()
    {
        return $this->typeElement;
    }
}

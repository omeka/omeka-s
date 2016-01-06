<?php
namespace Omeka\View\Helper;

use Zend\Form\Element\Select;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class DataType extends AbstractHelper
{
    protected $manager;

    protected $valueOptions = [];

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->manager = $serviceLocator->get('Omeka\DataTypeManager');
        foreach ($this->manager->getRegisteredNames() as $name) {
            $this->valueOptions[$name] = $this->manager->get($name)->getLabel();
        }
    }

    /**
     * Get the data type select markup.
     *
     * @param string $name
     * @param string $value
     */
    public function getSelect($name, $value)
    {
        $element = new Select($name);
        $element->setValueOptions($this->valueOptions);
        if (!array_key_exists($value, $this->valueOptions)) {
            $value = 'normal';
        }
        $element->setValue($value);
        return $this->getView()->formSelect($element);
    }
}

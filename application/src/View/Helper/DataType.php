<?php
namespace Omeka\View\Helper;

use Zend\Form\Element\Select;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class DataType extends AbstractHelper
{
    protected $manager;

    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->manager = $serviceLocator->get('Omeka\DataTypeManager');
    }

    public function getSelect($name, $value = null)
    {
        $element = new Select($name);
        $element->setValueOptions([
            'default' => 'Default',
            'literal' => 'Literal',
            'uri' => 'URI',
            'resource' => 'Resource',
        ]);
        return $this->getView()->formSelect($element); 
    }
}

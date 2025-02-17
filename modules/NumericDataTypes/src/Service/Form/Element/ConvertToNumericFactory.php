<?php
namespace NumericDataTypes\Service\Form\Element;

use Interop\Container\ContainerInterface;
use NumericDataTypes\Form\Element\ConvertToNumeric;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ConvertToNumericFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new ConvertToNumeric;
        $element->setFormElementManager($services->get('FormElementManager'));
        return $element;
    }
}

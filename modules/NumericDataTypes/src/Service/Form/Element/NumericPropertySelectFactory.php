<?php
namespace NumericDataTypes\Service\Form\Element;

use Interop\Container\ContainerInterface;
use NumericDataTypes\Form\Element\NumericPropertySelect;
use Laminas\ServiceManager\Factory\FactoryInterface;

class NumericPropertySelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new NumericPropertySelect;
        $element->setEntityManager($services->get('Omeka\EntityManager'));
        return $element;
    }
}

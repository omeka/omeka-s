<?php
namespace NumericDataTypes\Service\FacetedBrowse\FacetType;

use NumericDataTypes\FacetedBrowse\FacetType\ValueGreaterThan;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class ValueGreaterThanFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new ValueGreaterThan($services->get('FormElementManager'));
    }
}

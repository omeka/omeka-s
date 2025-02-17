<?php
namespace NumericDataTypes\Service\FacetedBrowse\FacetType;

use NumericDataTypes\FacetedBrowse\FacetType\DateAfter;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

class DateAfterFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new DateAfter($services->get('FormElementManager'));
    }
}

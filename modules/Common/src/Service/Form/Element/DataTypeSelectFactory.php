<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\DataTypeSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DataTypeSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new DataTypeSelect(null, $options ?? []);
        return $element
            ->setDataTypeManager($services->get('Omeka\DataTypeManager'));
    }
}

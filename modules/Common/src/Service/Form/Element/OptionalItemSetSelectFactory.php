<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\OptionalItemSetSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OptionalItemSetSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new OptionalItemSetSelect(null, $options ?? []);
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}

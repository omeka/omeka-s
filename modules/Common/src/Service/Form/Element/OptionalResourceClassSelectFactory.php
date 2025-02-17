<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\OptionalResourceClassSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OptionalResourceClassSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new OptionalResourceClassSelect(null, $options ?? []);
        $element->setApiManager($services->get('Omeka\ApiManager'));
        $element->setEventManager($services->get('EventManager'));
        return $element;
    }
}

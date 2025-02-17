<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\OptionalPropertySelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OptionalPropertySelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new OptionalPropertySelect(null, $options ?? []);
        $element->setEventManager($services->get('EventManager'));
        $element->setApiManager($services->get('Omeka\ApiManager'));
        $element->setTranslator($services->get('MvcTranslator'));
        return $element;
    }
}

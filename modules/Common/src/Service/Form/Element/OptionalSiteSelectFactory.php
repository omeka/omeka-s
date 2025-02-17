<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\OptionalSiteSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OptionalSiteSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new OptionalSiteSelect(null, $options ?? []);
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}

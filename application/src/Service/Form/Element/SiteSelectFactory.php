<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Omeka\Form\Element\SiteSelect;
use Zend\ServiceManager\Factory\FactoryInterface;

class SiteSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new SiteSelect;
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}

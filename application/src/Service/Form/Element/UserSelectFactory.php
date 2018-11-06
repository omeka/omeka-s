<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Omeka\Form\Element\UserSelect;
use Zend\ServiceManager\Factory\FactoryInterface;

class UserSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new UserSelect;
        $element->setApiManager($services->get('Omeka\ApiManager'));
        return $element;
    }
}

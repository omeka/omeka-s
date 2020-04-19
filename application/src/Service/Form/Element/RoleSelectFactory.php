<?php
namespace Omeka\Service\Form\Element;

use Interop\Container\ContainerInterface;
use Laminas\Form\Element\Select;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RoleSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $acl = $services->get('Omeka\Acl');
        $roles = $acl->getRoleLabels();
        $element = new Select;
        $element->setValueOptions($roles);
        $element->setEmptyOption('Select role…'); // @translate
        return $element;
    }
}

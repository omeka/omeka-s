<?php declare(strict_types=1);

namespace Common\Service\Form\Element;

use Common\Form\Element\OptionalRoleSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class OptionalRoleSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        /** @var \Omeka\Permissions\Acl $acl */
        $acl = $services->get('Omeka\Acl');
        $roles = $acl->getRoleLabels();
        $element = new OptionalRoleSelect(null, $options ?? []);
        return $element
            ->setValueOptions($roles)
            ->setEmptyOption('Select role…'); // @translate
    }
}

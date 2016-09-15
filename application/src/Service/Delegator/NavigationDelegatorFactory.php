<?php
namespace Omeka\Service\Delegator;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\DelegatorFactoryInterface;

/**
 * Set the ACL to the navigation helper.
 */
class NavigationDelegatorFactory implements DelegatorFactoryInterface
{
    public function __invoke(ContainerInterface $container, $name,
        callable $callback, array $options = null
    ) {
        $navigation = $callback();
        $navigation->setAcl($container->get('Omeka\Acl'));
        return $navigation;
    }
}

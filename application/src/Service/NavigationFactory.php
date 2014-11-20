<?php
namespace Omeka\Service;

use Zend\Navigation\Service\AbstractNavigationFactory;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Compose a navigation object by configured name.
 *
 * Note that this factory is not registered in the service manager
 * configuration. It is used exclusively by Omeka\View\Helper\Nav.
 */
class NavigationFactory extends AbstractNavigationFactory
{
    public function setName($name)
    {
        $this->name = $name;
    }

    protected function getName()
    {
        return $this->name;
    }
}

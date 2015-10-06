<?php
namespace Omeka\ServiceManager;

use Zend\ServiceManager\AbstractPluginManager as ZendAbstractPluginManager;

abstract class AbstractPluginManager extends ZendAbstractPluginManager
{
    /**
     * Get registered names.
     *
     * An alternative to parent::getCanonicalNames(). Returns only the names
     * that are registered in configuration not any that were added afterwards.
     *
     * @return array
     */
    public function getRegisteredNames()
    {
        $services = $this->getRegisteredServices();
        return array_merge($services['invokableClasses'], $services['factories']);
    }
}

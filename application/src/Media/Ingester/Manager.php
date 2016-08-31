<?php
namespace Omeka\Media\Ingester;

use Omeka\Media\Ingester\IngesterInterface;
use Omeka\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $instanceOf = IngesterInterface::class;
    
    /**
     * {@inheritDoc}
     */
    public function get($name, $options = [],
        $usePeeringServiceManagers = true
    ) {
        try {
            $instance = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            $instance = new Fallback($name, $this->getServiceLocator()->get('MvcTranslator'));
        }
        return $instance;
    }
}

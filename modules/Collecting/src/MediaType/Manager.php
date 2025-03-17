<?php
namespace Collecting\MediaType;

use Omeka\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    protected $instanceOf = MediaTypeInterface::class;

    public function get($name, $options = [],
        $usePeeringServiceManagers = true
    ) {
        try {
            $instance = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            $instance = new Fallback($name, $this->creationContext->get('MvcTranslator'));
        }
        return $instance;
    }
}

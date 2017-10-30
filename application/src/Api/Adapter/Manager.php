<?php
namespace Omeka\Api\Adapter;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = AdapterInterface::class;

    /**
     * {@inheritDoc}
     */
    public function __construct($configOrContainerInstance = null, array $v3config = [])
    {
        parent::__construct($configOrContainerInstance, $v3config);
        $this->addInitializer(function ($serviceLocator, $instance) {
            $instance->setServiceLocator($serviceLocator);
        }, false);
    }
}

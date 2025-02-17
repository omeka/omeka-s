<?php
namespace ExtractMetadata\Mapper;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = MapperInterface::class;

    public function get($name, $options = [], $usePeeringServiceManagers = true)
    {
        return parent::get($name ?? '', $options, $usePeeringServiceManagers);
    }
}

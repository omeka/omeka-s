<?php
namespace ExtractMetadata\Extractor;

use Omeka\ServiceManager\AbstractPluginManager;

class Manager extends AbstractPluginManager
{
    protected $autoAddInvokableClass = false;

    protected $instanceOf = ExtractorInterface::class;

    public function get($name, $options = [], $usePeeringServiceManagers = true)
    {
        return parent::get($name ?? '', $options, $usePeeringServiceManagers);
    }
}

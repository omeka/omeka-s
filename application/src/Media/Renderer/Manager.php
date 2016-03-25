<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Exception;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\Exception\ServiceNotFoundException;

class Manager extends AbstractPluginManager
{
    /**
     * {@inheritDoc}
     */
    protected $canonicalNamesReplacements = [];

    /**
     * {@inheritDoc}
     */
    public function get($name, $options = [],
        $usePeeringServiceManagers = true
    ) {
        try {
            $instance = parent::get($name, $options, $usePeeringServiceManagers);
        } catch (ServiceNotFoundException $e) {
            $instance = new Fallback;
        }
        return $instance;
    }

    /**
     * {@inheritDoc}
     */
    public function validatePlugin($plugin)
    {
        if (!is_subclass_of($plugin, 'Omeka\Media\Renderer\RendererInterface')) {
            throw new Exception\InvalidAdapterException(sprintf(
                'The media renderer class "%1$s" does not implement Omeka\Media\Renderer\RendererInterface.',
                get_class($plugin)
            ));
        }
    }
}

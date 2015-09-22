<?php
namespace Omeka\Media\Renderer;

use Omeka\Api\Exception;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;

class Manager extends AbstractPluginManager
{
    /**
     * {@inheritDoc}
     */
    protected $canonicalNamesReplacements = array();

    /**
     * {@inheritDoc}
     */
    public function __construct(ConfigInterface $configuration = null)
    {
        parent::__construct($configuration);
        $this->addInitializer(function($instance, $serviceLocator) {
            $instance->setServiceLocator($serviceLocator->getServiceLocator());
        }, false);
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

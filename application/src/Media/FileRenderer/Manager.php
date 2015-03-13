<?php
namespace Omeka\Media\FileRenderer;

use Omeka\Api\Exception;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Manager extends AbstractPluginManager
{
    /**
     * Do not replace strings during canonicalization.
     *
     * This prevents distinct yet similarly named media types from referencing
     * the same renderer instance.
     *
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
        if (!is_subclass_of($plugin, 'Omeka\Media\FileRenderer\RendererInterface')) {
            throw new Exception\InvalidAdapterException(sprintf(
                'The file renderer class "%1$s" does not implement Omeka\Media\FileRenderer\RendererInterface.',
                get_class($plugin)
            ));
        }
    }
}

<?php
namespace Omeka\BlockLayout;

use Omeka\Api\Exception;
use Omeka\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Manager extends AbstractPluginManager
{
    /**
     * Do not replace strings during canonicalization.
     *
     * This prevents distinct yet similarly named block layouts from referencing
     * the same handler instance.
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
        if (!is_subclass_of($plugin, 'Omeka\BlockLayout\BlockLayoutInterface')) {
            throw new Exception\InvalidAdapterException(sprintf(
                'The block layout class "%s" does not implement Omeka\BlockLayout\BlockLayoutInterface.',
                get_class($plugin)
            ));
        }
    }
}

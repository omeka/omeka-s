<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\ServiceManager\ConfigInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Manager extends AbstractPluginManager
{
    /**
     * Do not replace strings during canonicalization.
     *
     * This prevents distinct yet similarly named resources (such as "foo_bar"
     * and "foobar") from referencing the same adapter instance.
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
        $this->addInitializer(array($this, 'injectAdapterDependencies'), false);
    }

    /**
     * Inject required dependencies into the adapter.
     *
     * {@inheritDoc}
     */
    public function injectAdapterDependencies($adapter,
        ServiceLocatorInterface $serviceLocator
    ) {
        $adapter->setServiceLocator($serviceLocator->getServiceLocator());
    }

    /**
     * All API adapters must implement the following interfaces:
     *
     * - Omeka\Api\Adapter\AdapterInterface
     * - Zend\ServiceManager\ServiceLocatorAwareInterface
     * - Zend\EventManager\EventManagerAwareInterface
     * - Zend\Permissions\Acl\Resource\ResourceInterface
     *
     * {@inheritDoc}
     */
    public function validatePlugin($plugin)
    {
        if (!is_subclass_of($plugin, 'Omeka\Api\Adapter\AdapterInterface')) {
            throw new Exception\InvalidAdapterException(sprintf(
                'The adapter class "%1$s" does not implement Omeka\Api\Adapter\AdapterInterface.',
                get_class($plugin)
            ));
        }
    }
}

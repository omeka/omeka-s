<?php
namespace Omeka\Db\Event\Listener;

use Doctrine\Common\Persistence\Event\LoadClassMetadataEventArgs;
use Omeka\Event\FilterEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Load the resource discriminator map dynamically.
 */
class ResourceDiscriminatorMap
{
    /**
     * @var array Default entity resources
     */
    protected $defaultResources = array(
        'Omeka\Model\Entity\Item' => 'Omeka\Model\Entity\Item', 
        'Omeka\Model\Entity\Media' => 'Omeka\Model\Entity\Media', 
        'Omeka\Model\Entity\ItemSet' => 'Omeka\Model\Entity\ItemSet', 
    );
    
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Set the service locator.
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->services = $serviceLocator;
        $this->events = $serviceLocator->get('EventManager');
        $this->events->setIdentifiers(get_class($this));
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $event)
    {
        $classMetadata = $event->getClassMetadata();
        if ('Omeka\Model\Entity\Resource' != $classMetadata->name) {
            return;
        }

        // Modules can extend the resource discriminator map using a filter.
        $event = new FilterEvent;
        $event->setArg($this->defaultResources);
        $this->events->trigger(FilterEvent::RESOURCE_DISCRIMINATOR_MAP, $event);
        $this->defaultResources = $event->getArg();

        // Load default resources.
        $classMetadata->discriminatorMap = $this->defaultResources;
    }
}

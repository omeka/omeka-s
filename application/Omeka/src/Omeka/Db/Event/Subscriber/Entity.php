<?php
namespace Omeka\Db\Event\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as DoctrineEvent;
use Omeka\Event\Event as OmekaEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Entity event subscriber.
 *
 * Delegates selected Doctrine lifecycle events to Omeka events.
 */
class Entity implements EventSubscriber
{
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
    }

    public function getSubscribedEvents()
    {
        return array(
            DoctrineEvent::preRemove, DoctrineEvent::postRemove,
            DoctrineEvent::prePersist, DoctrineEvent::postPersist,
            DoctrineEvent::preUpdate, DoctrineEvent::postUpdate,
        );
    }

    /**
     * Trigger the entity.remove.pre event.
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->trigger(OmekaEvent::ENTITY_REMOVE_PRE, $args);
    }

    /**
     * Trigger the entity.remove.post event.
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $this->trigger(OmekaEvent::ENTITY_REMOVE_POST, $args);
    }

    /**
     * Trigger the entity.persist.pre event.
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->trigger(OmekaEvent::ENTITY_PERSIST_PRE, $args);
    }

    /**
     * Trigger the entity.persist.post event.
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->trigger(OmekaEvent::ENTITY_PERSIST_POST, $args);
    }

    /**
     * Trigger the entity.update.pre event.
     *
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->trigger(OmekaEvent::ENTITY_UPDATE_PRE, $args);
    }

    /**
     * Trigger the entity.update.post event.
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->trigger(OmekaEvent::ENTITY_UPDATE_POST, $args);
    }

    /**
     * Compose and trigger the event.
     *
     * @param string $eventName
     * @param LifecycleEventArgs $args
     */
    protected function trigger($eventName, LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $this->events->setIdentifiers(get_class($entity));
        $event = new OmekaEvent($eventName, $entity, array(
            'services' => $this->services,
        ));
        $this->events->trigger($event);
    }
}

<?php
namespace Omeka\Db\Event\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Events as DoctrineEvent;
use Omeka\Entity\Resource as OmekaResource;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\Event as ZendEvent;

/**
 * Entity event subscriber.
 *
 * Delegates selected Doctrine lifecycle events to Omeka events.
 */
class Entity implements EventSubscriber
{
    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * Set the service locator.
     */
    public function __construct(EventManagerInterface $events)
    {
        $this->events = $events;
    }

    public function getSubscribedEvents()
    {
        return [
            DoctrineEvent::preRemove, DoctrineEvent::postRemove,
            DoctrineEvent::prePersist, DoctrineEvent::postPersist,
            DoctrineEvent::preUpdate, DoctrineEvent::postUpdate,
        ];
    }

    /**
     * Trigger the entity.remove.pre event.
     *
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->trigger('entity.remove.pre', $args);
    }

    /**
     * Trigger the entity.remove.post event.
     *
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $this->trigger('entity.remove.post', $args);
    }

    /**
     * Trigger the entity.persist.pre event.
     *
     * @param LifecycleEventArgs $args
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->trigger('entity.persist.pre', $args);
    }

    /**
     * Trigger the entity.persist.post event.
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->trigger('entity.persist.post', $args);
    }

    /**
     * Trigger the entity.update.pre event.
     *
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->trigger('entity.update.pre', $args);
    }

    /**
     * Trigger the entity.update.post event.
     *
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->trigger('entity.update.post', $args);
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
        $identifiers = [get_class($entity)];
        if ($entity instanceof OmekaResource) {
            // Add the identifier for a generic resource entity.
            $identifiers[] = 'Omeka\Entity\Resource';
        }
        $this->events->setIdentifiers($identifiers);
        $event = new ZendEvent($eventName, $entity);
        $this->events->triggerEvent($event);
    }
}

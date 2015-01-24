<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Util\ClassUtils;

/**
 * Abstract entity.
 */
abstract class AbstractEntity implements EntityInterface
{
    /**
     * {@inheritDoc}
     */
    public function getResourceId()
    {
        // Get the real name of this entity, even if it is a Doctrine proxy.
        return ClassUtils::getClass($this);
    }

    /**
     * Synchronize a One-To-Many (bidirectional) association.
     *
     * Call this on the owning side of a One-To-Many association, in the entity
     * setter. On the inverse side, association management methods should only
     * call the owning setter.
     *
     * Passing an entity will add the entity to the inverse collection and set
     * it to the owning property. If an entity is already set, and is not the
     * same as the passed entity, it is removed from the inverse collection.
     * Passing null will remove the entity from the inverse collection and unset
     * the owning property.
     *
     * @param EntityInterface|null $entity The passed entity
     * @param string $property The name of the owning property that contains the entity
     * @param string $getter The name of the inverse method that gets the array collection
     */
    protected function synchronizeOneToMany(
        EntityInterface $entity = null, $property, $getter
    ) {
        if ($entity === $this->$property) {
            return;
        }
        if ($this->$property) {
            $this->$property->$getter()->removeElement($this);
        }
        if ($entity) {
            $entity->$getter()->add($this);
        }
        $this->$property = $entity;
    }
}

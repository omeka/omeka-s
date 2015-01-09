<?php
namespace Omeka\Api\Adapter\Entity;

use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

/**
 * Trait for owned entities.
 */
trait OwnedEntityTrait
{
    /**
     * Set the entity owner.
     *
     * @param array $data
     * @param EntityInterface $entity
     * @param bool $isManaged
     */
    public function setOwner(array $data, EntityInterface $entity, $isManaged)
    {
        $owner = $entity->getOwner();
        if (array_key_exists('o:owner', $data)
            && array_key_exists('o:id', $data['o:owner'])
        ) {
            // The owner is explicitly set.
            if (is_numeric($data['o:owner']['o:id'])
                && $owner->getId() != $data['o:owner']['o:id']
            ) {
                // Set a new owner if the owner ID is numeric and not the
                // current owner ID.
                $owner = $this->getAdapter('users')
                    ->findEntity($data['o:owner']['o:id']);
            } elseif (!$data['o:owner']['o:id']) {
                // Unset the owner if the owner ID resolves to false.
                $owner = null;
            }
            // Ignore all other owner IDs.
        } elseif (!$isManaged) {
            // By default, new entities are owned by the current user.
            $owner = $this->getServiceLocator()
                ->get('Omeka\AuthenticationService')
                ->getIdentity();
        }
        $entity->setOwner($owner);
    }
}

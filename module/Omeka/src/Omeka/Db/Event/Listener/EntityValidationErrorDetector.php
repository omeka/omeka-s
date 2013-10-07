<?php
namespace Omeka\Db\Event\Listener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Omeka\Model\Entity\EntityInterface;

/**
 * Detect entity validation errors.
 */
class EntityValidationErrorDetector
{
    /**
     * Throw an entity validation exception if errors are detected.
     *
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $uow = $eventArgs->getEntityManager()->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof EntityInterface
                && $entity->hasValidationErrors()
            ) {
                throw $entity->getValidationException();
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof EntityInterface
                && $entity->hasValidationErrors()
            ) {
                throw $entity->getValidationException();
            }
        }
    }
}

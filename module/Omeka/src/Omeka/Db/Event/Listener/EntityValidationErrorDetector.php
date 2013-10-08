<?php
namespace Omeka\Db\Event\Listener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Omeka\Error\Map as ErrorMap;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Exception\EntityValidationException;

/**
 * Detect entity validation errors.
 */
class EntityValidationErrorDetector
{
    /**
     * Detect entity validation errors.
     *
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            $this->validateEntity($entity, false, $entityManager);
        }
        foreach ($unitOfWork->getScheduledEntityUpdates() as $entity) {
            $this->validateEntity($entity, true, $entityManager);
        }
    }

    /**
     * Validate an entity.
     *
     * @param string $state insert|update
     * @param EntityInterface $entity
     * @param EntityManager $entityManager
     */
    protected function validateEntity(EntityInterface $entity, $isPersistent, 
        EntityManager $entityManager
    ) {
        if (!$entity instanceof EntityInterface) {
            return;
        }
        $errorMap = new ErrorMap;
        $entity->validate($errorMap, $isPersistent, $entityManager);
        if ($errorMap->hasErrors()) {
            $exception = new EntityValidationException('Entity validation failed.');
            $exception->setErrorMap($errorMap);
            throw $exception;
        }
    }
}

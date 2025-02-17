<?php
namespace Mapping\Db\Event\Listener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Mapping\Entity\Mapping;
use Mapping\Entity\MappingFeature;

/**
 * Automatically detach mappings and features that reference unknown items.
 */
class DetachOrphanMappings
{
    /**
     * Detach all Mapping entities that reference Items not currently in the
     * entity manager.
     *
     * @param PreFlushEventArgs $event
     */
    public function preFlush(PreFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $identityMap = $uow->getIdentityMap();

        if (isset($identityMap[Mapping::class])) {
            foreach ($identityMap[Mapping::class] as $mapping) {
                if (!$em->contains($mapping->getItem())) {
                    $em->detach($mapping);
                }
            }
        }

        if (isset($identityMap[MappingFeature::class])) {
            foreach ($identityMap[MappingFeature::class] as $feature) {
                if (!$em->contains($feature->getItem())
                    || ($feature->getMedia() && !$em->contains($feature->getMedia()))
                ) {
                    $em->detach($feature);
                }
            }
        }

        $insertions = $uow->getScheduledEntityInsertions();
        foreach ($insertions as $entity) {
            if (($entity instanceof Mapping && !$em->contains($entity->getItem()))
                || ($entity instanceof MappingFeature && (!$em->contains($entity->getItem()) || ($entity->getMedia() && !$em->contains($entity->getMedia()))))
            ) {
                $em->detach($entity);
            }
        }
    }
}

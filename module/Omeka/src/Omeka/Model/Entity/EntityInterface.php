<?php
namespace Omeka\Model\Entity;

use Doctrine\ORM\EntityManager;
use Omeka\Error\Store as ErrorStore;

/**
 * Entity API adapter interface.
 */
interface EntityInterface
{
    public function validate(ErrorStore $errorStore, $isPersistent,
        EntityManager $entityManager);
}

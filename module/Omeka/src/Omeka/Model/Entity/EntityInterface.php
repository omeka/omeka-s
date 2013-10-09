<?php
namespace Omeka\Model\Entity;

use Doctrine\ORM\EntityManager;
use Omeka\StdLib\ErrorStore;

/**
 * Entity API adapter interface.
 */
interface EntityInterface
{
    public function validate(ErrorStore $errorStore, $isPersistent,
        EntityManager $entityManager);
}

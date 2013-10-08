<?php
namespace Omeka\Model\Entity;

use Doctrine\ORM\EntityManager;
use Omeka\Error\Map as ErrorMap;

/**
 * Entity API adapter interface.
 */
interface EntityInterface
{
    public function validate(ErrorMap $errorMap, $isPersistent,
        EntityManager $entityManager);
}

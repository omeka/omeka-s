<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Stdlib\ErrorStore;
use Omeka\Model\Entity\EntityInterface;

/**
 * Entity API adapter interface.
 */
interface EntityInterface
{
    /**
     * Get this entity's fully qualified class name.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Build a conditional search query from an API request.
     *
     * @param array $query
     * @param QueryBuilder $queryBuilder
     */
    public function buildQuery(array $query, QueryBuilder $queryBuilder);

    /**
     * Validate an entity.
     *
     * @param EntityInterface $entity
     * @param ErrorStore $errorStore
     * @param bool $isPersistent
     */
    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent);
}

<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Stdlib\ErrorStore;
use Omeka\Model\Entity\EntityInterface;

/**
 * Entity API adapter interface.
 */
interface EntityAdapterInterface
{
    /**
     * Get the fully qualified class name of the entity.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Build a conditional search query from an API request.
     *
     * Modify the passed $queryBuilder object according to the passed $query.
     * The sort_by, sort_order, limit, and offset parameters are included
     * automatically.
     *
     * @link http://docs.doctrine-project.org/en/latest/reference/query-builder.html
     * @param array $query
     * @param QueryBuilder $queryBuilder
     */
    public function buildQuery(array $query, QueryBuilder $queryBuilder);

    /**
     * Validate an entity.
     *
     * Set validation errors to the passed $errorStore object. If an error is
     * present the entity will not be persisted or updated.
     *
     * @param EntityInterface $entity
     * @param ErrorStore $errorStore
     * @param bool $isPersistent
     */
    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent);
}

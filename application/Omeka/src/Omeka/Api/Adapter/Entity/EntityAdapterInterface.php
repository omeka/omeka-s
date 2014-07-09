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
     * Get the fully qualified name of the entity class.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Hydrate an entity with the provided array.
     *
     * Do not modify or perform operations on the data when setting properties.
     * Validation should be done in self::validate(). Filtering should be done
     * in the entity's mutator methods. Authorize state changes of individual
     * fields using self::authorize().
     *
     * @param array $data
     * @param EntityInterface $entity
     * @param ErrorStore $errorStore
     */
    public function hydrate(array $data, EntityInterface $entity,
        ErrorStore $errorStore);

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

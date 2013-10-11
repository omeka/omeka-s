<?php
namespace Omeka\Api\Adapter\Entity;

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
     * Find entities by API request query.
     *
     * @param array $query
     * @return array An array of entities
     */
    public function findByQuery(array $query);

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

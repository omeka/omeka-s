<?php
namespace Omeka\Api\Adapter;

use Omeka\Stdlib\ErrorStore;
use Omeka\Model\Entity\EntityInterface;

/**
 * Database API adapter interface.
 */
interface DbInterface
{
    /**
     * Get this entity's fully qualified class name.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Find entities by API request data.
     *
     * @param array $data
     * @return array An array of entities
     */
    public function findByData(array $data);

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

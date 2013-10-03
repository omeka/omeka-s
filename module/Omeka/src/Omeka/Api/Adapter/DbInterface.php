<?php
namespace Omeka\Api\Adapter;

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
     * Set API request data to an entity.
     *
     * @param \Omeka\Model\Entity\EntityInterface $entity
     * @param array $data
     */
    public function setData($entity, array $data);

    /**
     * Serialize an entity for an API response.
     *
     * @param \Omeka\Model\Entity\EntityInterface $entity
     * @return array
     */
    public function toArray($entity);

    /**
     * Find entities by API request data.
     *
     * @param array $data
     * @return array An array of entities
     */
    public function findByData(array $data);
}

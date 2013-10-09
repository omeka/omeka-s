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
     * Find entities by API request data.
     *
     * @param array $data
     * @return array An array of entities
     */
    public function findByData(array $data);
}

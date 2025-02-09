<?php
namespace Omeka\Api\Adapter;

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
}

<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Stdlib\ErrorStore;
use Omeka\Entity\EntityInterface;

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

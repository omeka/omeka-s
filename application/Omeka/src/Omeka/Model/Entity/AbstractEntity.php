<?php
namespace Omeka\Model\Entity;

/**
 * Abstract entity.
 */
abstract class AbstractEntity implements EntityInterface
{
    /**
     * {@inheritDoc}
     */
    public function getResourceId()
    {
        return get_class($this);
    }
}

<?php
namespace Omeka\Entity;

use Doctrine\Common\Util\ClassUtils;

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
        // Get the real name of this entity, even if it is a Doctrine proxy.
        return ClassUtils::getClass($this);
    }
}

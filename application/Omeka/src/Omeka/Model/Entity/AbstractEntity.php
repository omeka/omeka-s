<?php
namespace Omeka\Model\Entity;

use Zend\Permissions\Acl\Resource\ResourceInterface;

/**
 * Abstract entity.
 */
abstract class AbstractEntity implements EntityInterface, ResourceInterface
{
    /**
     * {@inheritDoc}
     */
    public function getResourceId()
    {
        return get_class($this);
    }
}

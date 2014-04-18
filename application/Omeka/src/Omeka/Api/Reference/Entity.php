<?php
namespace Omeka\Api\Reference;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Model\Entity\EntityInterface;

/**
 * A reference to a Doctrine entity.
 */
class Entity extends Reference
{
    /**
     * {@inheritDoc}
     */
    public function setData($data)
    {
        if (!$data instanceof EntityInterface) {
            throw new \Exception;
        }
        parent::setData($data);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        // The adapter's extract method transforms the data.
        return $this->adapter->extract($this->data);
    }
}

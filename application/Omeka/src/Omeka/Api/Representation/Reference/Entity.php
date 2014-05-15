<?php
namespace Omeka\Api\Representation\Reference;

use Omeka\Api\Representation\AbstractRepresentation;

class Entity extends Reference
{
    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        $adapter = $this->getAdapter($this->getResourceName());
        return $adapter->extract($this->data);
    }
}

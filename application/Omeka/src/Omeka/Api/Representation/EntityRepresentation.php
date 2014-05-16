<?php
namespace Omeka\Api\Representation;

class EntityRepresentation extends ResourceRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        $adapter = $this->getAdapter($this->getResourceName());
        return $adapter->extract($this->getData());
    }
}

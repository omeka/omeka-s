<?php
namespace Omeka\Api\Representation\Entity;

use Omeka\Api\Representation\ResourceRepresentation;

class EntityRepresentation extends ResourceRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        $adapter = $this->getAdapter($this->getResourceName());
        return $adapter->extract($this->getData())->toArray();
    }
}

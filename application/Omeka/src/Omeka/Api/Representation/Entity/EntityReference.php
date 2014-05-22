<?php
namespace Omeka\Api\Representation\Entity;

/**
 * A reference representation of an entity resource.
 *
 * Provides the minimal representation of an entity resource.
 */
class EntityReference extends AbstractEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getRepresentation()
    {
        return $this->getAdapter()
            ->getRepresentation($this->getId(), $this->getData());
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return array(
            '@id' => $this->getAdapter()->getApiUrl($this->getData()),
            'id'  => $this->getId(),
        );
    }
}

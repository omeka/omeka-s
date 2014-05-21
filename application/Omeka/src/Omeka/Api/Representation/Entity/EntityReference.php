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
    public function extract()
    {
        return $this->getAdapter()
            ->getRepresentation($this->getId(), $this->getData())
            ->extract();
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

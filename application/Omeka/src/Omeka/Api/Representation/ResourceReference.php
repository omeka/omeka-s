<?php
namespace Omeka\Api\Representation;

/**
 * A reference representation of an API resource.
 *
 * Provides the minimal representation of a resource.
 */
class ResourceReference extends AbstractResourceRepresentation
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
            'o:id'  => $this->getId(),
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {}
}

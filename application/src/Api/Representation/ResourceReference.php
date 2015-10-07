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
            ->getRepresentation($this->resource);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            '@id' => $this->apiUrl(),
            'o:id'  => $this->id(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLdType()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {}
}

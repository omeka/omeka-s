<?php
namespace Omeka\Api\Representation;

class ApiResourceRepresentation extends AbstractResourceRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getJsonLdType()
    {
        return 'o:ApiResource';
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {
        return [
            'o:id' => $this->resource->getId(),
        ];
    }
}

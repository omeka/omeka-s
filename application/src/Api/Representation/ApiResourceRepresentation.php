<?php
namespace Omeka\Api\Representation;

class ApiResourceRepresentation extends AbstractResourceRepresentation
{
    public function getJsonLdType()
    {
        return 'o:ApiResource';
    }

    public function getJsonLd()
    {
        return [
            'o:id' => $this->resource->getId(),
        ];
    }
}

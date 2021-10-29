<?php
namespace Omeka\Api\Representation;

class DataTypeRepresentation extends AbstractResourceRepresentation
{
    public function getJsonLdType()
    {
        return 'o:DataType';
    }

    public function getJsonLd()
    {
        return [
            'o:id' => $this->resource->getId(),
        ];
    }
}

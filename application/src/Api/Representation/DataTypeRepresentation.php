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
        $dataType = $this->getServiceLocator()
            ->get('Omeka\DataTypeManager')
            ->get($this->resource->getId());
        return [
            'o:id' => $this->resource->getId(),
            'o:label' => $dataType->getLabel(),
        ];
    }
}

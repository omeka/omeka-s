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
            'o:label' => $this->getLabel(),
        ];
    }

    public function getName()
    {
        return $this->resource->getId();
    }

    public function getLabel()
    {
        $dataType = $this->getServiceLocator()
            ->get('Omeka\DataTypeManager')
            ->get($this->resource->getId());
        return $dataType->getLabel();
    }
}

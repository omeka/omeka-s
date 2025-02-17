<?php
namespace Mapping\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class MappingRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-mapping:Map';
    }

    public function getJsonLd()
    {
        return [
            'o:item' => $this->item()->getReference(),
            'o-module-mapping:bounds' => $this->bounds(),
        ];
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function bounds()
    {
        return $this->resource->getBounds();
    }
}

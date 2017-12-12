<?php
namespace Omeka\Api\Representation;

class ModuleRepresentation extends AbstractResourceRepresentation
{
    public function getJsonLdType()
    {
        return 'o:Module';
    }

    public function getJsonLd()
    {
        return [
            'o:state' => $this->resource->getState(),
            'o:ini' => $this->resource->getIni(),
        ];
    }
}

<?php
namespace Omeka\Api\Representation;

class ModuleRepresentation extends AbstractResourceRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getJsonLdType()
    {
        return 'o:Module';
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {
        return [
            'o:state' => $this->resource->getState(),
            'o:ini' => $this->resource->getIni(),
        ];
    }
}

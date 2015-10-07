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
            'o:state' => $this->getData()->getState(),
            'o:ini' => $this->getData()->getIni(),
        ];
    }
}

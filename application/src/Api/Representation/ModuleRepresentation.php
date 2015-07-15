<?php
namespace Omeka\Api\Representation;

class ModuleRepresentation extends AbstractResourceRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getJsonLd()
    {
        return array(
            'o:state' => $this->getData()->getState(),
            'o:ini' => $this->getData()->getIni(),
        );
    }
}

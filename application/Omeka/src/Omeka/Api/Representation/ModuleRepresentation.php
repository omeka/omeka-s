<?php
namespace Omeka\Api\Representation;

class ModuleRepresentation extends Representation
{
    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->getData();
    }
}

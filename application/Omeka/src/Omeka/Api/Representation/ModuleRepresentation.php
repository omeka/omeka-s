<?php
namespace Omeka\Api\Representation;

class ModuleRepresentation extends AbstractRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}

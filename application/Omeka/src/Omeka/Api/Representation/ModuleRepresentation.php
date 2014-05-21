<?php
namespace Omeka\Api\Representation;

class ModuleRepresentation extends AbstractResourceRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function extract()
    {
        return $this->getData();
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return $this->getData();
    }
}

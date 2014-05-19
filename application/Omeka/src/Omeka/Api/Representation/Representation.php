<?php
namespace Omeka\Api\Representation;

class Representation extends AbstractRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Serialize as a simple JSON-LD object.
     *
     * Typically used for simple representations, such as references. Override
     * this method to compose a more complex JSON-LD object.
     *
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return array(
            '@id' => $this->getAdapter()->getApiUrl($this->getData()),
            'id'  => $this->getId(),
        );
    }
}

<?php
namespace Omeka\Api\Representation\Reference;

use Omeka\Api\Representation\AbstractRepresentation;

class Reference extends AbstractRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        $adapter = $this->getAdapter($this->getResourceName());
        return array(
            '@id' => $adapter->getApiUrl($this->getData()),
        );
    }
}

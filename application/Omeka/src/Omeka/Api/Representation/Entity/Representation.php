<?php
namespace Omeka\Api\Representation\Entity;

use Omeka\Api\Representation\AbstractRepresentation;

class Representation extends AbstractRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        $adapter = $this->getAdapter($this->getResourceName());
        return $adapter->extract($this->getData())->toArray();
    }
}

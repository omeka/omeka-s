<?php
namespace Omeka\Api\Representation\Entity;

use Omeka\Api\Representation\Representation as ResourceRepresentation;

class Representation extends ResourceRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function extract()
    {
        $adapter = $this->getAdapter($this->getResourceName());
        return $adapter->extract($this->getData())->toArray();
    }
}

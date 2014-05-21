<?php
namespace Omeka\Api\Representation\Entity;

use Omeka\Api\Representation\Representation as BaseRepresentation;

class Representation extends BaseRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function extract()
    {
        return $this->getAdapter()
            ->getRepresentation($this->getId(), $this->getData())
            ->extract();
    }
}

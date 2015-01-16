<?php
namespace Omeka\Api\Representation\Entity;

class ItemSetRepresentation extends AbstractResourceEntityRepresentation
{
    /**
     * {@inheritDoc}
     */
    public function getControllerName()
    {
        return 'item-set';
    }

    /**
     * {@inheritDoc}
     */
    public function getResourceJsonLd()
    {
        return array();
    }

    /**
     * Get this set's item count.
     *
     * @return int
     */
    public function itemCount()
    {
        return count($this->getData()->getItems());
    }
}

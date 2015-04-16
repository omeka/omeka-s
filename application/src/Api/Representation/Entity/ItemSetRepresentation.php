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

    /**
     * Return the first media of the first item.
     *
     * {@inheritDoc}
     */
    public function primaryMedia()
    {
        $itemEntities = $this->getData()->getItems();
        if ($itemEntities->isEmpty()) {
            return null;
        }
        $item = $this->getAdapter('items')
            ->getRepresentation(null, $itemEntities[0]);
        return $item->primaryMedia();
    }
}

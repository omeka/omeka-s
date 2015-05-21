<?php
namespace Omeka\Api\Representation;

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
        return array(
            'o:is_open' => $this->isOpen(),
        );
    }

    /**
     * Get this set's item count.
     *
     * @return int
     */
    public function itemCount()
    {
        $response = $this->getServiceLocator()->get('Omeka\ApiManager')
            ->search('items', array(
                'item_set_id' => $this->id(),
                'limit' => 0,
            ));
        return $response->getTotalResults();
    }

    /**
     * Get whether this set is open or not open.
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->getData()->isOpen();
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

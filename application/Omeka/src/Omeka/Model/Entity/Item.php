<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Model\Entity\ItemSet;

/**
 * @Entity
 */
class Item extends Resource
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @ManyToMany(targetEntity="ItemSet")
     */
    protected $itemSets;

    public function __construct() {
        parent::__construct();
        $this->itemSets = new ArrayCollection;
    }

    public function getResourceName()
    {
        return 'items';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getItemSets()
    {
        return $this->itemSets;
    }

    /**
     * Add this item to an item set.
     *
     * @param ItemSet $itemSet
     */
    public function addItemSet(ItemSet $itemSet)
    {
        $this->getItemSets()->add($itemSet);
    }

    /**
     * Remove this item from an item set.
     *
     * @return bool
     */
    public function removeItemSet(ItemSet $itemSet)
    {
        return $this->getItemSets()->removeElement($itemSet);
    }
}

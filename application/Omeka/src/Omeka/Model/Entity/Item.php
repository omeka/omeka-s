<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;

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
     * @ManyToMany(targetEntity="ItemSet", inversedBy="items")
     * @JoinTable(name="item_item_set")
     */
    protected $itemSets;

    /**
     * @OneToMany(targetEntity="SiteItem", mappedBy="item")
     */
    protected $sites;

    public function __construct() {
        parent::__construct();
        $this->sites = new ArrayCollection;
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
    public function addToItemSet(ItemSet $itemSet)
    {
        $itemSet->getItems()->add($this);
        $this->getItemSets()->add($itemSet);
    }

    /**
     * Remove this item from an item set.
     *
     * @param ItemSet $itemSet
     * @return bool
     */
    public function removeFromItemSet(ItemSet $itemSet)
    {
        $itemSet->getItems()->removeElement($this);
        return $this->getItemSets()->removeElement($itemSet);
    }

    public function getSites()
    {
        return $this->sites;
    }
}

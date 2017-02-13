<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class ItemSet extends Resource
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(type="boolean")
     */
    protected $isOpen = false;

    /**
     * @ManyToMany(targetEntity="Item", mappedBy="itemSets", fetch="EXTRA_LAZY")
     * @JoinTable(name="item_item_set")
     */
    protected $items;

    /**
     * @OneToMany(targetEntity="SiteItemSet", mappedBy="itemSet")
     */
    protected $siteItemSets;

    public function __construct()
    {
        parent::__construct();
        $this->items = new ArrayCollection;
        $this->siteItemSets = new ArrayCollection;
    }

    public function getResourceName()
    {
        return 'item_sets';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIsOpen($isOpen)
    {
        $this->isOpen = (bool) $isOpen;
    }

    public function isOpen()
    {
        return (bool) $this->isOpen;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function getSiteItemSets()
    {
        return $this->siteItemSets;
    }
}

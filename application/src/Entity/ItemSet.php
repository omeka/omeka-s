<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ItemSet extends Resource
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isOpen = false;

    /**
     * @ORM\ManyToMany(targetEntity="Item", mappedBy="itemSets", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="item_item_set")
     */
    protected $items;

    /**
     * @ORM\OneToMany(targetEntity="SiteItemSet", mappedBy="itemSet")
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

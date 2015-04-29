<?php
namespace Omeka\Entity;

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
     * @OneToMany(
     *     targetEntity="Media",
     *     mappedBy="item",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove", "detach"}
     * )
     */
    protected $media;

    /**
     * @OneToMany(targetEntity="SiteItem", mappedBy="item")
     */
    protected $siteItems;

    /**
     * @ManyToMany(targetEntity="ItemSet", inversedBy="items", indexBy="id")
     * @JoinTable(name="item_item_set")
     */
    protected $itemSets;

    public function __construct() {
        parent::__construct();
        $this->media = new ArrayCollection;
        $this->siteItems = new ArrayCollection;
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

    public function getMedia()
    {
        return $this->media;
    }

    public function getSiteItems()
    {
        return $this->siteItems;
    }

    public function getItemSets()
    {
        return $this->itemSets;
    }
}

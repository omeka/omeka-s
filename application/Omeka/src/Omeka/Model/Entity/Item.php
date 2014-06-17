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
     * @Column(type="boolean")
     */
    protected $isPublic = false;

    /**
     * @Column(type="boolean")
     */
    protected $isShareable = false;

    /**
     * @OneToMany(
     *     targetEntity="Media",
     *     mappedBy="item",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $media;

    /**
     * @OneToMany(targetEntity="SiteItem", mappedBy="item")
     */
    protected $sites;

    /**
     * @ManyToMany(targetEntity="ItemSet", inversedBy="items")
     * @JoinTable(name="item_item_set")
     */
    protected $itemSets;

    public function __construct() {
        parent::__construct();
        $this->media = new ArrayCollection;
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

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function isPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setIsShareable($isShareable)
    {
        $this->isShareable = (bool) $isShareable;
    }

    public function isShareable()
    {
        return (bool) $this->isShareable;
    }

    public function getMedia()
    {
        return $this->media;
    }

    /**
     * Add media to this item.
     *
     * @param Media $media
     */
    public function addMedia(Media $media)
    {
        $media->setItem($this);
        $this->getMedia()->add($media);
    }

    /**
     * Remove media from this item.
     *
     * @param Media $media
     * return bool
     */
    public function removeMedia(Media $media)
    {
        $media->setItem(null);
        return $this->getMedia()->removeElement($media);
    }

    public function getSites()
    {
        return $this->sites;
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
}

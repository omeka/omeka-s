<?php
namespace Omeka\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class Site extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(length=190, unique=true)
     */
    protected $slug;

    /**
     * @Column(length=190)
     */
    protected $theme;

    /**
     * @Column(length=190)
     */
    protected $title;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $summary;

    /**
     * @ManyToOne(targetEntity="Asset")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $thumbnail;

    /**
     * @Column(type="json")
     */
    protected $navigation;

    /**
     * @OneToOne(targetEntity="SitePage")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $homepage;

    /**
     * @Column(type="json")
     */
    protected $itemPool;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="sites")
     * @JoinColumn(onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $modified;

    /**
     * @Column(type="boolean")
     */
    protected $isPublic = true;

    /**
     * @Column(type="boolean", options={"default":false})
     */
    protected $assignNewItems = false;

    /**
     * @OneToMany(
     *     targetEntity="SitePage",
     *     mappedBy="site",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"},
     *     indexBy="id"
     * )
     */
    protected $pages;

    /**
     * @OneToMany(
     *     targetEntity="SitePermission",
     *     mappedBy="site",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $sitePermissions;

    /**
     * @OneToMany(
     *     targetEntity="SiteItemSet",
     *     mappedBy="site",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     * @OrderBy({"position" = "ASC"})
     */
    protected $siteItemSets;

    /**
     * @ManyToMany(targetEntity="Item", mappedBy="sites", fetch="EXTRA_LAZY")
     * @JoinTable(name="item_site")
     */
    protected $items;

    public function __construct()
    {
        $this->siteItems = new ArrayCollection;
        $this->pages = new ArrayCollection;
        $this->sitePermissions = new ArrayCollection;
        $this->siteItemSets = new ArrayCollection;
        $this->items = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;
    }

    public function getTheme()
    {
        return $this->theme;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setSummary($summary)
    {
        $this->summary = $summary;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function setThumbnail(Asset $thumbnail = null)
    {
        $this->thumbnail = $thumbnail;
    }

    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    public function setNavigation($navigation)
    {
        $this->navigation = $navigation;
    }

    public function getNavigation()
    {
        return $this->navigation;
    }

    public function setHomepage(SitePage $homepage = null)
    {
        $this->homepage = $homepage;
    }

    public function getHomepage()
    {
        return $this->homepage;
    }

    public function setItemPool($itemPool)
    {
        $this->itemPool = $itemPool;
    }

    public function getItemPool()
    {
        return $this->itemPool;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setCreated(DateTime $dateTime)
    {
        $this->created = $dateTime;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setModified(DateTime $dateTime)
    {
        $this->modified = $dateTime;
    }

    public function getModified()
    {
        return $this->modified;
    }

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function isPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setAssignNewItems($assignNewItems)
    {
        $this->assignNewItems = (bool) $assignNewItems;
    }

    public function getAssignNewItems()
    {
        return $this->assignNewItems;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function getSitePermissions()
    {
        return $this->sitePermissions;
    }

    public function getSiteItemSets()
    {
        return $this->siteItemSets;
    }

    public function getItems()
    {
        return $this->items;
    }
}

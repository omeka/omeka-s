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
     * @Column(type="json_array")
     */
    protected $navigation;

    /**
     * @Column(type="json_array")
     */
    protected $itemPool;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="sites")
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

    public function __construct()
    {
        $this->siteItems = new ArrayCollection;
        $this->pages = new ArrayCollection;
        $this->sitePermissions = new ArrayCollection;
        $this->siteItemSets = new ArrayCollection;
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

    public function setNavigation($navigation)
    {
        $this->navigation = $navigation;
    }

    public function getNavigation()
    {
        return $this->navigation;
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
}

<?php
namespace Omeka\Entity;

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
     * @ManyToOne(targetEntity="User", inversedBy="sites")
     */
    protected $owner;

    /**
     * @OneToMany(targetEntity="SiteItem", mappedBy="site")
     */
    protected $siteItems;

    /**
     * @OneToMany(targetEntity="SitePage", mappedBy="site")
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

    public function __construct()
    {
        $this->siteItems = new ArrayCollection;
        $this->pages = new ArrayCollection;
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

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getSiteItems()
    {
        return $this->siteItems;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function getSitePermissions()
    {
        return $this->sitePermissions;
    }
}

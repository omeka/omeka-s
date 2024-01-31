<?php
namespace Omeka\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"site_id", "slug"}
 *         )
 *     },
 *     indexes={
 *         @Index(
 *             name="is_public",
 *             columns={"is_public"}
 *         )
 *     }
 * )
 */
class SitePage extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(length=190)
     */
    protected $slug;

    /**
     * @Column(length=190)
     */
    protected $title;

    /**
     * @Column(type="boolean")
     */
    protected $isPublic = true;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $layout;

    /**
     * @Column(type="json", nullable=true)
     */
    protected $layoutData;

    /**
     * @ManyToOne(targetEntity="Site", inversedBy="pages")
     * @JoinColumn(nullable=false)
     */
    protected $site;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $modified;

    /**
     * @OneToMany(
     *     targetEntity="SitePageBlock",
     *     mappedBy="page",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     * @OrderBy({"position" = "ASC"})
     */
    protected $blocks;

    public function __construct()
    {
        $this->blocks = new ArrayCollection;
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

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function isPublic()
    {
        return (bool) $this->isPublic;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function setLayoutData($layoutData)
    {
        $this->layoutData = $layoutData;
    }

    public function getLayoutData()
    {
        return $this->layoutData;
    }

    public function setSite(Site $site)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        return $this->site;
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

    public function getBlocks()
    {
        return $this->blocks;
    }

    public function getOwner()
    {
        return $this->getSite()->getOwner();
    }
}

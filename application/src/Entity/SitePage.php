<?php
namespace Omeka\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"site_id", "slug"}
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

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->created = new DateTime('now');
    }

    /**
     * @PreUpdate
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $this->modified = new DateTime('now');
    }
}

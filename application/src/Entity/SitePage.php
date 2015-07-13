<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
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
     * @OneToMany(targetEntity="SitePageBlock", mappedBy="page")
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

    public function getBlocks()
    {
        return $this->blocks;
    }

    public function getOwner()
    {
        return $this->getSite()->getOwner();
    }
}

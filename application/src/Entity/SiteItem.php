<?php
namespace Omeka\Entity;

/**
 * @Entity
 */
class SiteItem extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;
    
    /**
     * @ManyToOne(targetEntity="User")
     */
    protected $assigner;
    
    /**
     * @ManyToOne(targetEntity="Site", inversedBy="siteItems")
     * @JoinColumn(nullable=false)
     */
    protected $site;
    
    /**
     * @ManyToOne(targetEntity="Item", inversedBy="siteItems")
     * @JoinColumn(nullable=false)
     */
    protected $item;
    
    public function getId()
    {
        return $this->id;
    }

    public function setAssigner(User $assigner)
    {
        $this->assigner = $assigner;
    }

    public function getAssigner()
    {
        return $this->assigner;
    }

    public function setSite(Site $site = null)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setItem(Item $item = null)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }
}

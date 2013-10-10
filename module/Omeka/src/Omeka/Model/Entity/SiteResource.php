<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class SiteResource extends AbstractEntity
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** @ManyToOne(targetEntity="User") */
    protected $assigner;
    
    /**
     * @ManyToOne(targetEntity="Site", inversedBy="sites")
     * @JoinColumn(nullable=false)
     */
    protected $site;
    
    /**
     * @ManyToOne(targetEntity="Resource", inversedBy="resources")
     * @JoinColumn(nullable=false)
     */
    protected $resource;
    
    public function getId()
    {
        return $this->id;
    }

    public function setAssigner($assigner)
    {
        $this->assigner = $assigner;
    }

    public function getAssigner()
    {
        return $this->assigner;
    }

    public function setSite($site)
    {
        $this->site = $site;
    }

    public function getSite()
    {
        return $this->site;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }
}

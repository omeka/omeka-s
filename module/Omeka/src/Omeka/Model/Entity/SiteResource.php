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

    public function toArray()
    {
    }
}

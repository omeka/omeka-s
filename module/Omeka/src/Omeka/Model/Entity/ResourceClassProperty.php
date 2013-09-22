<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class ResourceClassProperty
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** @ManyToOne(targetEntity="User") */
    protected $assigner;
    
    /**
     * @ManyToOne(targetEntity="ResourceClass", inversedBy="properties")
     * @JoinColumn(nullable=false)
     */
    protected $resourceClass;
    
    /**
     * @ManyToOne(targetEntity="Property", inversedBy="resourceClasses")
     * @JoinColumn(nullable=false)
     */
    protected $property;
    
    /** @Column(nullable=true) */
    protected $alternateLabel;
    
    /** @Column(type="text", nullable=true) */
    protected $alternateComment;
    
    public function getId()
    {
        return $this->id;
    }
}

<?php
namespace Omeka\Model\Entity;

/**
 * A resource class.
 * 
 * Classes are logical groupings of resources that have specified ranges of 
 * descriptive properties.
 * 
 * @Entity
 * @Table(uniqueConstraints={@UniqueConstraint(name="default_resource_type", columns={"resource_type", "is_default"})})
 */
class ResourceClass extends AbstractEntity
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** @ManyToOne(targetEntity="User") */
    protected $owner;
    
    /** @ManyToOne(targetEntity="Vocabulary") */
    protected $vocabulary;
    
    /** @OneToMany(targetEntity="ResourceClassProperty", mappedBy="resourceClass") */
    protected $properties;
    
    /** @Column(nullable=true) */
    protected $localName;
    
    /** @Column */
    protected $label;
    
    /** @Column(type="text", nullable=true) */
    protected $comment;
    
    /** @Column */
    protected $resourceType;
    
    /** @Column(type="boolean") */
    protected $isDefault;
    
    public function __construct()
    {
        $this->properties = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getVocabulary()
    {
        return $this->vocabulary;
    }
    
    public function setVocabulary($vocabulary)
    {
        $this->vocabulary = $vocabulary;
    }
    
    public function getLocalName()
    {
        return $this->localName;
    }
    
    public function setLocalName($localName)
    {
        $this->localName = $localName;
    }
    
    public function getLabel()
    {
        return $this->label;
    }
    
    public function setLabel($label)
    {
        $this->label = $label;
    }
    
    public function getComment()
    {
        return $this->comment;
    }
    
    public function setComment($comment)
    {
        $this->comment = $comment;
    }
    
    public function getResourceType()
    {
        return $this->resourceType;
    }
    
    public function setResourceType($resourceType)
    {
        if (!in_array($resourceType, array(
            Resource::TYPE_ITEM_SET, 
            Resource::TYPE_ITEM, 
            Resource::TYPE_MEDIA
        ))) {
            throw new \InvalidArgumentException('Invalid resource type');
        }
        $this->resourceType = $resourceType;
    }
    
    public function getIsDefault()
    {
        return $this->defaultClass;
    }
    
    public function setIsDefault($isDefault)
    {
        $this->isDefault = (bool) $isDefault;
    }
}

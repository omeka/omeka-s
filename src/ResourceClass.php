<?php
/**
 * @Entity
 * @Table(uniqueConstraints={@UniqueConstraint(name="default_resource_type", columns={"resource_type_id", "default"})})
 */
class ResourceClass
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    /** @ManyToOne(targetEntity="ResourceType") @JoinColumn(nullable=false) */
    protected $resourceType;
    
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
    
    /** @Column(type="boolean") */
    protected $defaultClass;
    
    public function __construct()
    {
        $this->properties = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getResourceType()
    {
        return $this->resourceType;
    }
    
    public function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;
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
    
    public function getDefaultClass()
    {
        return $this->defaultClass;
    }
    
    public function setDefaultClass($default)
    {
        $this->defaultClass = $defaultClass;
    }
    
}

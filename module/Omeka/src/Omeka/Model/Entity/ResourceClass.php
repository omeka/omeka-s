<?php
namespace Omeka\Model\Entity;

use \Doctrine\Common\Collections\ArrayCollection;

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
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User")
     */
    protected $owner;

    /**
     * @ManyToOne(targetEntity="Vocabulary")
     */
    protected $vocabulary;

    /**
     * @OneToMany(targetEntity="ResourceClassProperty", mappedBy="resourceClass")
     */
    protected $properties;

    /**
     * @Column(nullable=true)
     */
    protected $localName;

    /**
     * @Column
     */
    protected $label;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $comment;

    /**
     * @Column
     */
    protected $resourceType;

    /**
     * @Column(type="boolean", nullable=true)
     */
    protected $isDefault;

    public function __construct()
    {
        $this->properties = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setVocabulary($vocabulary)
    {
        $this->vocabulary = $vocabulary;
    }

    public function getVocabulary()
    {
        return $this->vocabulary;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    public function setLocalName($localName)
    {
        $this->localName = $localName;
    }

    public function getLocalName()
    {
        return $this->localName;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    public function getComment()
    {
        return $this->comment;
    }

    public function setResourceType($resourceType)
    {
        $this->resourceType = $resourceType;
    }

    public function getResourceType()
    {
        return $this->resourceType;
    }

    public function setIsDefault($isDefault)
    {
        // Must be true or null for the resource_type/is_default unique
        // constraint to work.
        $this->isDefault = $isDefault ? (bool) $isDefault : null;
    }

    public function getIsDefault()
    {
        return $this->isDefault;
    }

}

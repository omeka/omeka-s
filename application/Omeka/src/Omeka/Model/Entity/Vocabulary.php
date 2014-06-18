<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A vocabulary.
 * 
 * Vocabularies are defined sets of classes and properties.
 * 
 * @Entity
 */
class Vocabulary extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="vocabularies")
     */
    protected $owner;

    /**
     * @Column(unique=true)
     */
    protected $namespaceUri;

    /**
     * @Column(unique=true)
     */
    protected $prefix;

    /**
     * @Column
     */
    protected $label;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $comment;

    /**
     * @OneToMany(
     *     targetEntity="ResourceClass",
     *     mappedBy="vocabulary",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $resourceClasses;

    /**
     * @OneToMany(
     *     targetEntity="Property",
     *     mappedBy="vocabulary",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $properties;

    public function __construct()
    {
        $this->resourceClasses = new ArrayCollection;
        $this->properties = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(User $owner = null)
    {
        $this->synchronizeOneToMany($owner, 'owner', 'getVocabularies');
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setNamespaceUri($namespaceUri)
    {
        $this->namespaceUri = $namespaceUri;
    }

    public function getNamespaceUri()
    {
        return $this->namespaceUri;
    }

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getPrefix()
    {
        return $this->prefix;
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

    public function getResourceClasses()
    {
        return $this->resourceClasses;
    }

    /**
     * Add a resource class to this vocabulary.
     *
     * @param ResourceClass $resourceClass
     */
    public function addResourceClass(ResourceClass $resourceClass)
    {
        $resourceClass->setVocabulary($this);
    }

    /**
     * Remove a resource class from this vocabulary.
     *
     * @param ResourceClass $resourceClass
     * @return bool
     */
    public function removeResourceClass(ResourceClass $resourceClass)
    {
        $resourceClass->setVocabulary(null);
    }

    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Add a property to this vocabulary.
     *
     * @param Property $property
     */
    public function addProperty(Property $property)
    {
        $property->setVocabulary($this);
    }

    /**
     * Remove a property from this vocabulary.
     *
     * @param Property $property
     */
    public function removeProperty(Property $property)
    {
        $property->setVocabulary(null);
    }

}

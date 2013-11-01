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
 * @Table(
 *     options={"collate"="utf8_bin"},
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             name="default_resource_type",
 *             columns={"resource_type", "is_default"}
 *         ),
 *         @UniqueConstraint(
 *             name="vocabulary_local_name",
 *             columns={"vocabulary_id", "local_name"}
 *         )
 *     }
 * )
 *
 * @todo Once the following Doctrine DBAL bug is resolved, move the utf8_bin
 * collation to the localName column, using options={"collation"="utf8_bin"}.
 * That particular collation is needed so unique constraints are case sensitive.
 * http://www.doctrine-project.org/jira/browse/DBAL-647 
 */
class ResourceClass implements EntityInterface
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
     * @Column(nullable=true)
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

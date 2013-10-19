<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A property, representing the predicate in an RDF triple.
 * 
 * Properties define relationships between resources and their values.
 * 
 * @Entity
 * @Table(uniqueConstraints={
 *     @UniqueConstraint(name="vocabulary_local_name", columns={"vocabulary_id", "local_name"})
 * })
 */
class Property extends AbstractEntity
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
     * @OneToMany(targetEntity="ResourceClassProperty", mappedBy="property")
     */
    protected $resourceClasses;

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

    public function __construct()
    {
        $this->resourceClasses = new ArrayCollection;
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

    public function getResourceClasses()
    {
        return $this->resourceClasses;
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
}

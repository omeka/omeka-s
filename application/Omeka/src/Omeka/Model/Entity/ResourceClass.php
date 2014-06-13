<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;

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
     * @ManyToOne(targetEntity="Vocabulary", inversedBy="resourceClasses")
     */
    protected $vocabulary;

    /**
     * @Column
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
     * @OneToMany(
     *     targetEntity="PropertyOverrideSet",
     *     mappedBy="resourceClass",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $propertyOverrideSets;

    public function __construct()
    {
        $this->propertyOverrideSets = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setVocabulary(Vocabulary $vocabulary = null)
    {
        if ($vocabulary instanceof Vocabulary) {
            $vocabulary->getResourceClasses()->add($this);
        }
        $this->vocabulary = $vocabulary;
    }

    public function getVocabulary()
    {
        return $this->vocabulary;
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

    public function getPropertyOverrideSets()
    {
        return $this->propertyOverrideSets;
    }

    /**
     * Add a property override set to this resource class.
     *
     * @param PropertyOverrideSet $propertyOverrideSet
     */
    public function addPropertyOverrideSet(PropertyOverrideSet $propertyOverrideSet)
    {
        $propertyOverrideSet->setResourceClass($this);
        $this->getPropertyOverrideSets()->add($propertyOverrideSet);
    }

    /**
     * Remove a property override set from this resource class.
     *
     * @param PropertyOverrideSet $propertyOverrideSet
     * @return bool
     */
    public function removePropertyOverrideSet(PropertyOverrideSet $propertyOverrideSet)
    {
        $propertyOverrideSet->setResourceClass(null);
        return $this->getPropertyOverrideSets()->removeElement($propertyOverrideSet);
    }
}

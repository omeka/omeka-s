<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * A resource class.
 * 
 * Classes are logical groupings of resources that have specified ranges of 
 * descriptive properties.
 * 
 * @Entity(repositoryClass="Omeka\Model\Repository\ResourceClass")
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
     * @ManyToOne(targetEntity="User", inversedBy="resourceClasses")
     */
    protected $owner;

    /**
     * @ManyToOne(targetEntity="Vocabulary", inversedBy="resourceClasses")
     * @JoinColumn(nullable=false)
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
     *     targetEntity="PropertyAssignmentSet",
     *     mappedBy="resourceClass",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $propertyAssignmentSets;

    public function __construct()
    {
        $this->propertyAssignmentSets = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(User $owner = null)
    {
        $this->synchronizeOneToMany($owner, 'owner', 'getResourceClasses');
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setVocabulary(Vocabulary $vocabulary = null)
    {
        $this->synchronizeOneToMany($vocabulary, 'vocabulary', 'getResourceClasses');
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

    public function getPropertyAssignmentSets()
    {
        return $this->propertyAssignmentSets;
    }

    /**
     * Add a property assignment set to this resource class.
     *
     * @param PropertyAssignmentSet $propertyAssignmentSet
     */
    public function addPropertyAssignmentSet(PropertyAssignmentSet $propertyAssignmentSet)
    {
        $propertyAssignmentSet->setResourceClass($this);
    }

    /**
     * Remove a property assignment set from this resource class.
     *
     * @param PropertyAssignmentSet $propertyAssignmentSet
     * @return bool
     */
    public function removePropertyAssignmentSet(PropertyAssignmentSet $propertyAssignmentSet)
    {
        $propertyAssignmentSet->setResourceClass(null);
    }
}

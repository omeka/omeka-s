<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Model\Entity\PropertyAssignment;
use Omeka\Model\Entity\User;

/**
 * @Entity
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             name="resource_class_label",
 *             columns={"resource_class_id", "label"}
 *         )
 *     }
 * )
 */
class PropertyAssignmentSet extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column
     */
    protected $label;

    /**
     * @ManyToOne(targetEntity="ResourceClass", inversedBy="propertyAssignmentSets")
     * @JoinColumn(nullable=false)
     */
    protected $resourceClass;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="propertyAssignmentSets")
     */
    protected $owner;

    /**
     * @OneToMany(
     *     targetEntity="PropertyAssignment",
     *     mappedBy="propertyAssignmentSet",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $propertyAssignments;

    public function __construct()
    {
        $this->propertyAssignments = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setResourceClass(ResourceClass $resourceClass = null)
    {
        $this->synchronizeOneToMany($resourceClass, 'resourceClass',
            'getPropertyAssignmentSets');
    }

    public function getResourceClass()
    {
        return $this->resourceClass;
    }

    public function setOwner(User $owner = null)
    {
        $this->synchronizeOneToMany($owner, 'owner', 'getPropertyAssignmentSets');
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getPropertyAssignments()
    {
        return $this->propertyAssignments;
    }

    /**
     * Add a property assignment to this set.
     *
     * @param PropertyAssignment $propertyAssignment
     */
    public function addPropertyAssignment(PropertyAssignment $propertyAssignment)
    {
        $propertyAssignment->setPropertyAssignmentSet($this);
    }

    /**
     * Remove a property assignment from this set.
     *
     * @param PropertyAssignment $propertyAssignment
     * @return bool
     */
    public function removePropertyAssignment(PropertyAssignment $propertyAssignment)
    {
        $propertyAssignment->setPropertyAssignmentSet(null);
    }
}

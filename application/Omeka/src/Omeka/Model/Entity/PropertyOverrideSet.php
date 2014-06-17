<?php
namespace Omeka\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Model\Entity\PropertyOverride;
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
class PropertyOverrideSet extends AbstractEntity
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
     * @ManyToOne(targetEntity="ResourceClass", inversedBy="propertyOverrideSets")
     * @JoinColumn(nullable=false)
     */
    protected $resourceClass;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="propertyOverrideSets")
     */
    protected $owner;

    /**
     * @OneToMany(
     *     targetEntity="PropertyOverride",
     *     mappedBy="propertyOverrideSet",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $propertyOverrides;

    public function __construct()
    {
        $this->propertyOverrides = new ArrayCollection;
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
        if ($resourceClass instanceof ResourceClass) {
            $resourceClass->getPropertyOverrideSets()->add($this);
        }
        $this->resourceClass = $resourceClass;
    }

    public function getResourceClass()
    {
        return $this->resourceClass;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function getPropertyOverrides()
    {
        return $this->propertyOverrides;
    }

    /**
     * Add a property override to this set.
     *
     * @param PropertyOverride $propertyOverride
     */
    public function addPropertyOverride(PropertyOverride $propertyOverride)
    {
        $propertyOverride->setPropertyOverrideSet($this);
        $this->getPropertyOverrides()->add($propertyOverride);
    }

    /**
     * Remove a property override from this set.
     *
     * @param PropertyOverride $propertyOverride
     * @return bool
     */
    public function removePropertyOverride(PropertyOverride $propertyOverride)
    {
        $propertyOverride->setPropertyOverrideSet(null);
        return $this->getPropertyOverrides()->removeElement($propertyOverride);
    }
}

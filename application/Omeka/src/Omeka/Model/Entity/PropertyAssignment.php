<?php
namespace Omeka\Model\Entity;

use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\PropertyAssignmentSet;

/**
 * @Entity
 */
class PropertyAssignment extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="PropertyAssignmentSet", inversedBy="propertyAssignments")
     * @JoinColumn(nullable=false)
     */
    protected $propertyAssignmentSet;

    /**
     * @ManyToOne(targetEntity="Property")
     * @JoinColumn(nullable=false)
     */
    protected $property;

    /**
     * @Column(nullable=true)
     */
    protected $alternateLabel;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $alternateComment;

    /**
     * @Column(type="boolean")
     */
    protected $default = true;

    public function getId()
    {
        return $this->id;
    }

    public function setPropertyAssignmentSet(PropertyAssignmentSet $propertyAssignmentSet = null)
    {
        $this->synchronizeOneToMany($propertyAssignmentSet, 'propertyAssignmentSet',
            'getPropertyAssignments');
    }

    public function getPropertyAssignmentSet()
    {
        return $this->propertyAssignmentSet;
    }

    public function setProperty(Property $property)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setAlternateLabel($alternateLabel)
    {
        $this->alternateLabel = $alternateLabel;
    }

    public function getAlternateLabel()
    {
        return $this->alternateLabel;
    }

    public function setAlternateComment($alternateComment)
    {
        $this->alternateComment = $alternateComment;
    }

    public function getAlternateComment()
    {
        return $this->alternateComment;
    }

    public function setDefault($default)
    {
        $this->default = (bool) $default;
    }

    public function isDefault()
    {
        return $this->default;
    }
}

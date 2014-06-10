<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class PropertyOverride extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="PropertyOverrideSet", inversedBy="propertyOverrides")
     * @JoinColumn(nullable=false)
     */
    protected $propertyOverrideSet;

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

    public function setResourceClass($resourceClass)
    {
        $this->resourceClass = $resourceClass;
    }

    public function getResourceClass()
    {
        return $this->resourceClass;
    }

    public function setProperty($property)
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

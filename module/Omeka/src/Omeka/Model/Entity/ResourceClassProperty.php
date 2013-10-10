<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class ResourceClassProperty extends AbstractEntity
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
    protected $assigner;

    /**
     * @ManyToOne(targetEntity="ResourceClass", inversedBy="properties")
     * @JoinColumn(nullable=false)
     */
    protected $resourceClass;

    /**
     * @ManyToOne(targetEntity="Property", inversedBy="resourceClasses")
     * @JoinColumn(nullable=false)
     */
    protected $property;

    /** @Column(nullable=true) */
    protected $alternateLabel;

    /** @Column(type="text", nullable=true) */
    protected $alternateComment;

    public function getId()
    {
        return $this->id;
    }

    public function setAssigner($assigner)
    {
        $this->assigner = $assigner;
    }

    public function getAssigner()
    {
        return $this->assigner;
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
}

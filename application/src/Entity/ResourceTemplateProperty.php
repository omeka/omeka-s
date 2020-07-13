<?php
namespace Omeka\Entity;

/**
 * @Entity
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"resource_template_id", "property_id", "data_type"}
 *         ),
 *         @UniqueConstraint(
 *             columns={"resource_template_id", "property_id", "alternate_label"}
 *         )
 *     }
 * )
 */
class ResourceTemplateProperty extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="ResourceTemplate", inversedBy="resourceTemplateProperties")
     * @JoinColumn(nullable=false)
     */
    protected $resourceTemplate;

    /**
     * @ManyToOne(targetEntity="Property")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
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
     * @Column(type="integer", nullable=true)
     */
    protected $position;

    /**
     * Since "dataType" is part of a constraint, it cannot be longer than 766
     * characters in utf8mb4 (3072 in ascii). It's enough to store 20 or 30 data
     * types, that is the max number set in the resource templates for ergonomic
     * reasons. The value suggest data type "all" is available too if a big
     * number of data types is really needed for a property. Furthermore, the
     * same data type cannot be added to the same property multiple times.
     * Else convert this column to ascii, create an associate table, or remove
     * the constraint in database.
     *
     * @Column(length=766, nullable=true)
     */
    protected $dataType;

    /**
     * @Column(type="boolean")
     */
    protected $isRequired = false;

    /**
     * @Column(type="boolean")
     */
    protected $isPrivate = false;

    public function getId()
    {
        return $this->id;
    }

    public function setResourceTemplate(ResourceTemplate $resourceTemplate = null)
    {
        $this->resourceTemplate = $resourceTemplate;
    }

    public function getResourceTemplate()
    {
        return $this->resourceTemplate;
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

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = (int) $position;
    }

    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    public function getDataType()
    {
        return $this->dataType;
    }

    public function setIsRequired($isRequired)
    {
        $this->isRequired = (bool) $isRequired;
    }

    public function getIsRequired()
    {
        return (bool) $this->isRequired;
    }

    public function isRequired()
    {
        return $this->getIsRequired();
    }

    public function setIsPrivate($isPrivate)
    {
        $this->isPrivate = (bool) $isPrivate;
    }

    public function getIsPrivate()
    {
        return (bool) $this->isPrivate;
    }

    public function isPrivate()
    {
        return $this->getIsPrivate();
    }
}

<?php
namespace Omeka\Entity;

/**
 * @Entity
 * @Table(
 *     uniqueConstraints={
 *         @UniqueConstraint(
 *             columns={"resource_template_id", "property_id"}
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
     * @Column(type="json_array", nullable=true)
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
        return $this;
    }

    public function getResourceTemplate()
    {
        return $this->resourceTemplate;
    }

    public function setProperty(Property $property)
    {
        $this->property = $property;
        return $this;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setAlternateLabel($alternateLabel)
    {
        $this->alternateLabel = $alternateLabel;
        return $this;
    }

    public function getAlternateLabel()
    {
        return $this->alternateLabel;
    }

    public function setAlternateComment($alternateComment)
    {
        $this->alternateComment = $alternateComment;
        return $this;
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
        return $this;
    }

    public function setDataType(array $dataType = null)
    {
        $this->dataType = $dataType;
        return $this;
    }

    public function getDataType()
    {
        return $this->dataType;
    }

    public function setIsRequired($isRequired)
    {
        $this->isRequired = (bool) $isRequired;
        return $this;
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
        return $this;
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

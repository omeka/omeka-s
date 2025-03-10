<?php
namespace Omeka\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             columns={"resource_template_id", "property_id"}
 *         )
 *     }
 * )
 */
class ResourceTemplateProperty extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="ResourceTemplate", inversedBy="resourceTemplateProperties")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $resourceTemplate;

    /**
     * @ORM\ManyToOne(targetEntity="Property")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $property;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $alternateLabel;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $alternateComment;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $position;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $dataType;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isRequired = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isPrivate = false;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $defaultLang;

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

    public function setDataType(array $dataType = null)
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

    public function setDefaultLang($defaultLang)
    {
        $this->defaultLang = $defaultLang;
    }

    public function getDefaultLang()
    {
        return $this->defaultLang;
    }
}

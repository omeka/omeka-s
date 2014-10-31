<?php
namespace Omeka\Model\Entity;

use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\ResourceTemplate;

/**
 * @Entity
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

    public function getId()
    {
        return $this->id;
    }

    public function setResourceTemplate(ResourceTemplate $resourceTemplate = null)
    {
        $this->synchronizeOneToMany($resourceTemplate, 'resourceTemplate',
            'getResourceTemplateProperties');
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
}

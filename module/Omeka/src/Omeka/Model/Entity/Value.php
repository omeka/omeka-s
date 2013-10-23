<?php
namespace Omeka\Model\Entity;

/**
 * A value, representing the object in a RDF triple.
 * 
 * @Entity
 */
class Value implements EntityInterface
{
    const TYPE_LITERAL = 'literal';
    const TYPE_RESOURCE = 'resource';
    const TYPE_URI = 'uri';

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="User")
     */
    protected $owner;

    /**
     * @ManyToOne(targetEntity="Resource")
     * @JoinColumn(nullable=false)
     */
    protected $resource;

    /**
     * @ManyToOne(targetEntity="Property")
     * @JoinColumn(nullable=false)
     */
    protected $property;

    /**
     * @Column
     */
    protected $type;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $value;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $valueTransformed;

    /**
     * @Column(nullable=true)
     */
    protected $lang;

    /**
     * @Column(type="boolean")
     */
    protected $isHtml;

    /**
     * @ManyToOne(targetEntity="Resource")
     */
    protected $valueResource;

    public function getId()
    {
        return $this->id;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setProperty($property)
    {
        $this->property = $property;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValueTransformed($valueTransformed)
    {
        $this->valueTransformed = $valueTransformed;
    }

    public function getValueTransformed()
    {
        return $this->valueTransformed;
    }

    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setIsHtml($isHtml)
    {
        $this->isHtml = $isHtml;
    }

    public function getIsHtml()
    {
        return $this->isHtml;
    }

    public function setValueResource($valueResource)
    {
        $this->valueResource = $valueResource;
    }

    public function getValueResource()
    {
        return $this->valueResource;
    }
}

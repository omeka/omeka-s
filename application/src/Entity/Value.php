<?php
namespace Omeka\Entity;

/**
 * A value, representing the object in a RDF triple.
 *
 * @Entity
 * @Table(name="`value`", indexes={
 *     @Index(name="`value`", columns={"`value`"}, options={"lengths":{190}}),
 *     @Index(name="`uri`", columns={"`uri`"}, options={"lengths":{190}})
 * })
 */
class Value extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Resource", inversedBy="values")
     * @JoinColumn(nullable=false)
     */
    protected $resource;

    /**
     * @ManyToOne(targetEntity="Property", inversedBy="values")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $property;

    /**
     * @Column
     */
    protected $type;

    /**
     * @Column(nullable=true)
     */
    protected $lang;

    /**
     * @Column(name="`value`", type="text", nullable=true)
     */
    protected $value;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $uri;

    /**
     * @ManyToOne(targetEntity="Resource")
     * @JoinColumn(onDelete="CASCADE")
     */
    protected $valueResource;

    /**
     * @Column(type="boolean")
     */
    protected $isPublic = true;

    public function getId()
    {
        return $this->id;
    }

    public function setResource(Resource $resource = null)
    {
        $this->resource = $resource;
        return $this;
    }

    public function getResource()
    {
        return $this->resource;
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

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setLang($lang)
    {
        $this->lang = $lang;
        return $this;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
        return $this;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function setValueResource(Resource $valueResource = null)
    {
        $this->valueResource = $valueResource;
        return $this;
    }

    public function getValueResource()
    {
        return $this->valueResource;
    }

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
        return $this;
    }

    public function getIsPublic()
    {
        return (bool) $this->isPublic;
    }

    public function isPublic()
    {
        return $this->getIsPublic();
    }
}

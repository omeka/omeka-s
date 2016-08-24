<?php
namespace Omeka\Entity;

/**
 * A value, representing the object in a RDF triple.
 *
 * @Entity
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
     * @Column(type="text", nullable=true)
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

    public function getId()
    {
        return $this->id;
    }

    public function setResource(Resource $resource = null)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setProperty(Property $property)
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

    public function setLang($lang)
    {
        $this->lang = $lang;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function setValueResource(Resource $valueResource = null)
    {
        $this->valueResource = $valueResource;
    }

    public function getValueResource()
    {
        return $this->valueResource;
    }
}

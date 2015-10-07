<?php
namespace Omeka\Entity;

/**
 * A value, representing the object in a RDF triple.
 * 
 * @Entity
 */
class Value extends AbstractEntity
{
    const TYPE_LITERAL = 'literal';
    const TYPE_RESOURCE = 'resource';
    const TYPE_URI = 'uri';

    /**
     * @var array
     */
    protected $validTypes = [
        self::TYPE_LITERAL,
        self::TYPE_RESOURCE,
        self::TYPE_URI,
    ];

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
     * @Column(type="text", nullable=true)
     */
    protected $value;

    /**
     * @Column(nullable=true)
     */
    protected $lang;

    /**
     * @Column(nullable=true)
     */
    protected $uriLabel;

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

    public function setUriLabel($uriLabel)
    {
        $this->uriLabel = $uriLabel;
    }

    public function getUriLabel()
    {
        return $this->uriLabel;
    }

    public function setValueResource(Resource $valueResource = null)
    {
        $this->valueResource = $valueResource;
    }

    public function getValueResource()
    {
        return $this->valueResource;
    }

    public function getValidTypes()
    {
        return $this->validTypes;
    }
}

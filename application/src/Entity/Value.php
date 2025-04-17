<?php
namespace Omeka\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * A value, representing the object in a RDF triple.
 *
 * @ORM\Entity
 * @ORM\Table(name="`value`", indexes={
 *     @ORM\Index(name="`value`", columns={"`value`"}, options={"lengths":{190}}),
 *     @ORM\Index(name="`uri`", columns={"`uri`"}, options={"lengths":{190}}),
 *     @ORM\Index(name="is_public", columns={"is_public"})
 * })
 */
class Value extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Resource", inversedBy="values")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $resource;

    /**
     * @ORM\ManyToOne(targetEntity="Property", inversedBy="values")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $property;

    /**
     * @ORM\Column
     */
    protected $type;

    /**
     * @ORM\Column(nullable=true)
     */
    protected $lang;

    /**
     * @ORM\Column(name="`value`", type="text", nullable=true)
     */
    protected $value;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $uri;

    /**
     * @ORM\ManyToOne(targetEntity="Resource")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $valueResource;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isPublic = true;

    /**
     * @ORM\OneToOne(targetEntity="ValueAnnotation", orphanRemoval=true, cascade={"persist"})
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    protected $valueAnnotation;

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

    public function setIsPublic($isPublic)
    {
        $this->isPublic = (bool) $isPublic;
    }

    public function getIsPublic()
    {
        return (bool) $this->isPublic;
    }

    public function isPublic()
    {
        return $this->getIsPublic();
    }

    public function setValueAnnotation(ValueAnnotation $valueAnnotation = null)
    {
        $this->valueAnnotation = $valueAnnotation;
    }

    public function getValueAnnotation()
    {
        return $this->valueAnnotation;
    }
}

<?php
namespace NumericDataTypes\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Property;
use Omeka\Entity\Resource;

/**
 * @MappedSuperclass
 * @Table(
 *     indexes={
 *         @Index(name="property_value", columns={"property_id", "value"}),
 *         @Index(name="value", columns={"value"}),
 *     }
 * )
 */
class NumericDataTypesNumber extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Resource"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $resource;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Property"
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $property;

    public function getId()
    {
        return $this->id;
    }

    public function setResource(Resource $resource)
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
}

<?php
namespace Omeka\Entity;

/**
 * @Entity
 */
class ValueAnnotation extends Resource
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="Value", mappedBy="valueAnnotation")
     */
    protected $value;

    public function getResourceName()
    {
        return 'value_annotations';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }
}

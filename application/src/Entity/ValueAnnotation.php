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

    public function getResourceName()
    {
        return 'value_annotations';
    }

    public function getId()
    {
        return $this->id;
    }
}

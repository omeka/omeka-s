<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;

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

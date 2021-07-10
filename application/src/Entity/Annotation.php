<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class Annotation extends Resource
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    public function getResourceName()
    {
        return 'annotations';
    }

    public function getId()
    {
        return $this->id;
    }
}

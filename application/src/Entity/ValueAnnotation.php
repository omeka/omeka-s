<?php
namespace Omeka\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class ValueAnnotation extends Resource
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
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

<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class File implements EntityInterface
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}

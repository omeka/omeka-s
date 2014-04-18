<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class Item extends Resource
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    public function getResourceName()
    {
        return 'items';
    }

    public function getId()
    {
        return $this->id;
    }
}

<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class ItemSet extends Resource
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }

    public function getAdapterClass()
    {
        return 'Omeka\Api\Adapter\Entity\ItemSet';
    }
}

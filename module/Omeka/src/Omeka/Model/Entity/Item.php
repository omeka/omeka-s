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

    public function getAdapterClass()
    {
        return 'Omeka\Api\Adapter\Entity\ItemAdapter';
    }

    public function getId()
    {
        return $this->id;
    }
}

<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class User extends AbstractEntity
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;
    
    public function getId()
    {
        return $this->id;
    }

    public function setData(array $data)
    {
    }

    public function toArray()
    {
    }
}

<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class File extends AbstractEntity
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}

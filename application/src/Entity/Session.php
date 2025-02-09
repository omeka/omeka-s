<?php
namespace Omeka\Entity;

/**
 * @Entity
 */
class Session
{
    /**
     * @Id
     * @Column(type="string", length=190)
     */
    protected $id;

    /**
     * @Column(type="blob")
     */
    protected $data;

    /**
     * @Column(type="integer")
     */
    protected $modified;
}

<?php
namespace Omeka\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Session
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=190)
     */
    protected $id;

    /**
     * @ORM\Column(type="blob")
     */
    protected $data;

    /**
     * @ORM\Column(type="integer")
     */
    protected $modified;
}

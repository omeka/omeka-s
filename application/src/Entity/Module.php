<?php
namespace Omeka\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Module extends AbstractEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=190)
     */
    protected $id;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isActive = false;

    /**
     * @ORM\Column(length=190)
     */
    protected $version;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }

    public function getVersion()
    {
        return $this->version;
    }
}

<?php
namespace Omeka\Entity;

/**
 * @Entity
 */
class Module extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="string", length=190)
     */
    protected $id;

    /**
     * @Column(type="boolean")
     */
    protected $isActive = false;

    /**
     * @Column
     */
    protected $version;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setIsActive($isActive)
    {
        $this->isActive = (bool) $isActive;
        return $this;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    public function getVersion()
    {
        return $this->version;
    }
}

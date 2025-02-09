<?php
namespace Omeka\Entity;

/**
 * @Entity
 */
class Migration extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="string", length=16)
     */
    protected $version;

    public function getId()
    {
        return $this->version;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }
}

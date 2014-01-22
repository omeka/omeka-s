<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class Migration implements EntityInterface
{
    /**
     * @Id
     * @Column(type="string", length=16)
     */
    protected $version;

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
    }
}

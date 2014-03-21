<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class Option extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="string", length=255)
     */
    protected $id;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $value;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}

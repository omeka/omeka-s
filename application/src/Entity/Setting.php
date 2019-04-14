<?php
namespace Omeka\Entity;

/**
 * @Entity
 */
class Setting extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="string", length=190)
     */
    protected $id;

    /**
     * @Column(type="json_array")
     */
    protected $value;

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }
}

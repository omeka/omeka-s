<?php
namespace Omeka\Entity;

/**
 * @Entity
 */
class UserSetting extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="string", length=190)
     */
    protected $id;

    /**
     * @Id
     * @ManyToOne(targetEntity="User")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @Column(type="json_array")
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

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
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

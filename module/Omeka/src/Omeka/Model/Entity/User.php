<?php
namespace Omeka\Model\Entity;

/**
 * @Entity
 */
class User extends AbstractEntity
{
    /** @Id @Column(type="integer") @GeneratedValue */
    protected $id;

    /** @Column(unique=true) */
    protected $username;
    
    public function getId()
    {
        return $this->id;
    }

    public function setUsername($username)
    {
        //~ if (1) {
            //~ $this->addValidationError('foo', 'foo message one');
            //~ $this->addValidationError('foo', 'foo message two');
        //~ }
        //~ if (1) {
            //~ $this->addValidationError('bar', 'bar message');
        //~ }
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }
}

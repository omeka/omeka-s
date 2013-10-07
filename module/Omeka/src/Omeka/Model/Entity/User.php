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
            //~ $this->setValidationError('foo', 'foo message one');
            //~ $this->setValidationError('foo', 'foo message two');
        //~ }
        //~ if (1) {
            //~ $this->setValidationError('bar', 'bar message');
        //~ }
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }
}

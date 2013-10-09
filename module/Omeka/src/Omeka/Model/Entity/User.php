<?php
namespace Omeka\Model\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\StdLib\ErrorStore;
use Omeka\Validator\Db\IsUnique;

/**
 * @Entity
 * @HasLifecycleCallbacks
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
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function validate(ErrorStore $errorStore, $isPersistent,
        EntityManager $entityManager
    ) {
        $validator = new IsUnique('username', $entityManager);
        if (!$validator->isValid($this)) {
            $errorStore->addValidatorMessages('username', $validator->getMessages());
        }
    }
}

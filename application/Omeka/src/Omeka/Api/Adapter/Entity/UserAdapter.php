<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Zend\Validator\EmailAddress;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class UserAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = array(
        'id'        => 'id',
        'username'  => 'username',
        'email'     => 'email',
        'name'      => 'name',
        'created'   => 'created',
        'modified'  => 'modified',
        'role'      => 'role',
    );

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'users';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\UserRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\User';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if (isset($data['o:username'])) {
            $entity->setUsername($data['o:username']);
        }
        if (isset($data['o:name'])) {
            $entity->setName($data['o:name']);
        }
        if (isset($data['o:email'])) {
            $entity->setEmail($data['o:email']);
        }
        if (isset($data['o:role'])) {
            $entity->setRole($data['o:role']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['username'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Model\Entity\User.username",
                $this->createNamedParameter($qb, $query['username']))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        if (empty($entity->getUsername())) {
            $errorStore->addError('o:username', 'The username cannot be empty.');
        }
        if (preg_match('/\s/u', $entity->getUsername())) {
            $errorStore->addError('o:username', 'A username cannot contain whitespace.');
        }
        if (!$this->isUnique($entity, array('username' => $entity->getUsername()))) {
            $errorStore->addError('o:username', 'The username is already taken.');
        }

        if (empty($entity->getName())) {
            $errorStore->addError('o:name', 'The name cannot be empty.');
        }

        $validator = new EmailAddress();
        if (!$validator->isValid($entity->getEmail())) {
            $errorStore->addValidatorMessages('o:email', $validator->getMessages());
        }
        if (!$this->isUnique($entity, array('email' => $entity->getEmail()))) {
            $errorStore->addError('o:email', 'The email is already taken.');
        }
    }
}

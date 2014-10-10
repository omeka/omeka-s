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
        $username = $entity->getUsername();
        if (empty($username)) {
            $errorStore->addError('o:username', 'The username field cannot be empty.');
        }
        if (preg_match('/\s/u', $username)) {
            $errorStore->addError('o:username', 'A username cannot contain whitespace.');
        }

        $name = $entity->getName();
        if (empty($name)) {
            $errorStore->addError('o:name', 'The name field cannot be null.');
        }
        $validator = new EmailAddress();
        if (!$validator->isValid($entity->getEmail())) {
            $errorStore->addValidatorMessages('o:email', $validator->getMessages());
        }
    }
}

<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Zend\Validator\EmailAddress;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
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
        return 'Omeka\Api\Representation\UserRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\User';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if ($this->shouldHydrate($request, 'o:username')) {
            $entity->setUsername($request->getValue('o:username'));
        }
        if ($this->shouldHydrate($request, 'o:name')) {
            $entity->setName($request->getValue('o:name'));
        }
        if ($this->shouldHydrate($request, 'o:email')) {
            $entity->setEmail($request->getValue('o:email'));
        }
        if ($this->shouldHydrate($request, 'o:role')) {
            $entity->setRole($request->getValue('o:role'));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['username'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Entity\User.username",
                $this->createNamedParameter($qb, $query['username']))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $username = $entity->getUsername();
        if (false == $username) {
            $errorStore->addError('o:username', 'The username cannot be empty.');
        }
        if (preg_match('/\s/u', $username)) {
            $errorStore->addError('o:username', 'A username cannot contain whitespace.');
        }
        if (!$this->isUnique($entity, array('username' => $username))) {
            $errorStore->addError('o:username', sprintf(
                'The username "%s" is already taken.',
                $username
            ));
        }

        if (false == $entity->getName()) {
            $errorStore->addError('o:name', 'The name cannot be empty.');
        }

        $email = $entity->getEmail();
        $validator = new EmailAddress();
        if (!$validator->isValid($email)) {
            $errorStore->addValidatorMessages('o:email', $validator->getMessages());
        }
        if (!$this->isUnique($entity, array('email' => $email))) {
            $errorStore->addError('o:email', sprintf(
                'The email "%s" is already taken.',
                $email
            ));
        }

        if (false == $entity->getRole()) {
            $errorStore->addError('o:role', 'Users must have a role.');
        }
    }
}

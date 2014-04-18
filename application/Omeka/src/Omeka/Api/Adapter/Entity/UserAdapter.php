<?php
namespace Omeka\Api\Adapter\Entity;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Zend\Validator\EmailAddress;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Validator\Db\IsUnique;

class UserAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\User';
    }

    public function hydrate(array $data, $entity)
    {
        if (isset($data['username'])) {
            $entity->setUsername($data['username']);
        }

        if (isset($data['name'])) {
            $entity->setName($data['name']);
        }

        if (isset($data['email'])) {
            $entity->setEmail($data['email']);
        }

        if (isset($data['role'])) {
            $entity->setRole($data['role']);
        }
    }

    public function extract($entity)
    {
        $extracted = array(
            '@id'      => $this->getApiUrl($entity),
            'id'       => $entity->getId(),
            'username' => $entity->getUsername(),
            'name'     => $entity->getName(),
            'email'    => $entity->getEmail(),
            'created'  => $entity->getCreated(),
            'role'     => $entity->getRole(),
        );

        if ($extracted['created'] instanceof DateTime) {
            $extracted['created'] = $extracted['created']->format('c');
        }

        return $extracted;
    }

    public function buildQuery(array $query, QueryBuilder $qb)
    {
        if (isset($query['username'])) {
            $this->andWhere($qb, 'username', $query['username']);
        }
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        $username = $entity->getUsername();
        if (empty($username)) {
            $errorStore->addError('username', 'The username field cannot be null.');
        }
        $name = $entity->getName();
        if (empty($name)) {
            $errorStore->addError('name', 'The name field cannot be null.');
        }
        $validator = new IsUnique(array('username'), $this->getEntityManager());
        if (!$validator->isValid($entity)) {
            $errorStore->addValidatorMessages('username', $validator->getMessages());
        }
        $validator = new IsUnique(array('email'), $this->getEntityManager());
        if (!$validator->isValid($entity)) {
            $errorStore->addValidatorMessages('email', $validator->getMessages());
        }
        $validator = new EmailAddress();
        if (!$validator->isValid($entity->getEmail())) {
            $errorStore->addValidatorMessages('email', $validator->getMessages());
        }
    }
}

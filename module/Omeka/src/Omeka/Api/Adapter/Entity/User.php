<?php
namespace Omeka\Api\Adapter\Entity;

use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Validator\Db\IsUnique;

class User extends AbstractEntity
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
    }

    public function extract($entity)
    {
        return array(
            'id' => $entity->getId(),
            'username' => $entity->getUsername(),
        );
    }

    public function findByQuery(array $query)
    {
        return $this->getEntityManager()
                    ->getRepository($this->getEntityClass())
                    ->findAll();
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        $validator = new IsUnique('username', $this->getEntityManager());
        if (!$validator->isValid($entity)) {
            $errorStore->addValidatorMessages('username', $validator->getMessages());
        }
    }
}

<?php
namespace Omeka\Api\Adapter;

class User extends AbstractDb
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

    public function findByData(array $data)
    {
        return $this->getEntityManager()
                    ->getRepository($this->getEntityClass())
                    ->findAll();
    }
}

<?php
namespace Omeka\Api\Adapter;

class User extends AbstractDb
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\User';
    }

    public function setData($entity, array $data)
    {
    }

    public function toArray($entity)
    {
        return array(
            'id' => $entity->getId(),
            'username' => $entity->getUsername(),
        );
    }
}

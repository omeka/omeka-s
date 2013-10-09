<?php
namespace Omeka\Api\Adapter;

class Vocabulary extends AbstractDb
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Vocabulary';
    }

    public function hydrate(array $data, $entity)
    {
        $owner = $this->getEntityManager()
                      ->getRepository('Omeka\Model\Entity\User')
                      ->find($data['owner']['id']);
        $entity->setOwner($owner);
        $entity->setNamespaceUri($data['namespace_uri']);
        $entity->setLabel($data['label']);
        $entity->setComment($data['comment']);
    }

    public function extract($entity)
    {
        $userAdapter = new User;
        return array(
            'owner' => $userAdapter->toArray($entity->getOwner()),
            'namespace_uri' => $entity->getNamespaceUri(),
            'label' => $entity->getLabel(),
            'comment' => $entity->getComment(),
        );
    }

    public function findByData(array $data)
    {
        return $this->getEntityManager()
                    ->getRepository($this->getEntityClass())
                    ->findAll();
    }
}

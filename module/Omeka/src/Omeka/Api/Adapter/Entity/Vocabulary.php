<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class Vocabulary extends AbstractEntity
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

    public function buildQuery(array $query, QueryBuilder $qb)
    {
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
    }
}

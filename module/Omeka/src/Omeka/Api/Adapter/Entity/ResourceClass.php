<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Validator\Db\IsUnique;

class ResourceClass extends AbstractEntity
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\ResourceClass';
    }

    public function hydrate(array $data, $entity)
    {
        $owner = $this->getEntityManager()
            ->getRepository('Omeka\Model\Entity\User')
            ->find($data['owner']['id']);
        $vocabulary = $this->getEntityManager()
            ->getRepository('Omeka\Model\Entity\Vocabulary')
            ->find($data['vocabulary']['id']);
        $entity->setOwner($owner);
        $entity->setVocabulary($vocabulary);
        $entity->setLocalName($data['local_name']);
        $entity->setLabel($data['label']);
        $entity->setComment($data['comment']);
        $entity->setResourceType($data['resource_type']);
        $entity->setIsDefault($data['is_default']);
    }

    public function extract($entity)
    {
        $userAdapter = new User;
        $vocabularyAdapter = new Vocabulary;
        return array(
            'id' => $entity->getId(),
            'owner' => $userAdapter->extract($entity->getOwner()),
            'vocabulary' => $vocabularyAdapter->extract($entity->getVocabulary()),
            'local_name' => $entity->getLocalName(),
            'label' => $entity->getLabel(),
            'comment' => $entity->getComment(),
            'resource_type' => $entity->getResourceType(),
            'is_default' => $entity->getIsDefault(),
        );
    }

    public function buildQuery(array $query, QueryBuilder $qb)
    {
        $entityClass = $this->getEntityClass();
        if (isset($query['owner_id'])) {
            $userAdapter = new User;
            $entityClassUser = $userAdapter->getEntityClass();
            $qb->addSelect($entityClassUser)
                ->innerJoin($entityClassUser, $entityClassUser,
                    'WITH', "$entityClassUser.id = $entityClass.owner")
                ->andWhere("$entityClassUser.id = :owner_id")
                ->setParameter('owner_id', $query['owner_id']);
        }
        if (isset($query['vocabulary_id'])) {
            $vocabularyAdapter = new Vocabulary;
            $entityClassVocabulary = $vocabularyAdapter->getEntityClass();
            $qb->addSelect($entityClassVocabulary)
                ->innerJoin($entityClassVocabulary, $entityClassVocabulary,
                    'WITH', "$entityClassVocabulary.id = $entityClass.vocabulary")
                ->andWhere("$entityClassVocabulary.id = :vocabulary_id")
                ->setParameter('vocabulary_id', $query['vocabulary_id']);
        }
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        $validator = new IsUnique(array('resourceType', 'isDefault'), $this->getEntityManager());
        if (!$validator->isValid($entity)) {
            $errorStore->addValidatorMessages('default_resource_type', $validator->getMessages());
        }
    }
}

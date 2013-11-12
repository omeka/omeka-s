<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Validator\Db\IsUnique;

class ResourceClassAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\ResourceClass';
    }

    public function hydrate(array $data, $entity)
    {
        if (isset($data['owner']['id'])) {
            $owner = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\User')
                ->find($data['owner']['id']);
            $entity->setOwner($owner);
        }
        if (isset($data['vocabulary']['id'])) {
            $vocabulary = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\Vocabulary')
                ->find($data['vocabulary']['id']);
            $entity->setVocabulary($vocabulary);
        }
        if (isset($data['local_name'])) {
            $entity->setLocalName($data['local_name']);
        }
        if (isset($data['label'])) {
            $entity->setLabel($data['label']);
        }
        if (isset($data['comment'])) {
            $entity->setComment($data['comment']);
        }
        if (isset($data['resource_type'])) {
            $entity->setResourceType($data['resource_type']);
        }
        if (isset($data['is_default'])) {
            $entity->setIsDefault($data['is_default']);
        }
    }

    public function extract($entity)
    {
        return array(
            'id' => $entity->getId(),
            'owner' => $this->extractEntity($entity->getOwner(), new UserAdapter),
            'vocabulary' => $this->extractEntity(
                $entity->getVocabulary(),
                new VocabularyAdapter
            ),
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
        if (isset($query['owner'])) {
            $userAdapter = new UserAdapter;
            $entityClassUser = $userAdapter->getEntityClass();
            $qb->addSelect($entityClassUser)
                ->innerJoin($entityClassUser, $entityClassUser,
                    'WITH', "$entityClassUser.id = $entityClass.owner");
            if ($query['owner']['id']) {
                $qb->andWhere("$entityClassUser.id = :owner_id")
                    ->setParameter('owner_id', $query['owner']['id']);
            }
        }
        if (isset($query['vocabulary'])) {
            $vocabularyAdapter = new VocabularyAdapter;
            $entityClassVocabulary = $vocabularyAdapter->getEntityClass();
            $qb->addSelect($entityClassVocabulary)
                ->innerJoin(
                    $entityClassVocabulary, $entityClassVocabulary,
                    'WITH', "$entityClassVocabulary.id = $entityClass.vocabulary"
                );
            if (isset($query['vocabulary']['id'])) {
                $qb->andWhere("$entityClassVocabulary.id = :vocabulary_id")
                    ->setParameter('vocabulary_id', $query['vocabulary']['id']);
            }
            if (isset($query['vocabulary']['namespace_uri'])) {
                $qb->andWhere("$entityClassVocabulary.namespaceUri = :vocabulary_namespace_uri")
                    ->setParameter('vocabulary_namespace_uri', $query['vocabulary']['namespace_uri']);
            }
        }
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        // Validate resourceType/isDefault unique constraint.
        $validator = new IsUnique(
            array('resourceType', 'isDefault'),
            $this->getEntityManager()
        );
        if (!$validator->isValid($entity)) {
            $errorStore->addValidatorMessages(
                'default_resource_type',
                $validator->getMessages()
            );
        }

        // Validate the vocabulary/localName unique constraint.
        $validator = new IsUnique(
            array('vocabulary', 'localName'),
            $this->getEntityManager()
        );
        if (!$validator->isValid($entity)) {
            $errorStore->addValidatorMessages(
                'vocabulary_local_name',
                $validator->getMessages()
            );
        }

        // Validate resourceType.
        $discriminatorMap = $this->getEntityManager()
            ->getClassMetadata('Omeka\Model\Entity\Resource')
            ->discriminatorMap;
        if (!in_array($entity->getResourceType(), $discriminatorMap)
            && !is_null($entity->getResourceType())
        ) {
            $errorStore->addError(
                'resource_type',
                'The provided resource_type is not a registered resource type.'
            );
        }

        // Validate label.
        if (null === $entity->getLabel()) {
            $errorStore->addError('label', 'The label field cannot be null.');
        }

        // Validate is_default.
        if (!is_bool($entity->getIsDefault()) && !is_null($entity->getIsDefault())) {
            $errorStore->addError(
                'is_default',
                'The is_default field must be boolean or null.'
            );
        }
    }
}

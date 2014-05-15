<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Validator\Db\IsUnique;

class PropertyAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Property';
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

    }

    public function extract($entity)
    {
        return array(
            '@id'        => $this->getApiUrl($entity),
            'id'         => $entity->getId(),
            'local_name' => $entity->getLocalName(),
            'label'      => $entity->getLabel(),
            'comment'    => $entity->getComment(),
            'vocabulary' => $this->getReference('vocabularies', $entity->getVocabulary()),
            'owner'      => $this->getReference('users', $entity->getOwner()),
        );
    }

    public function buildQuery(array $query, QueryBuilder $qb)
    {
        if (isset($query['owner']['id'])) {
            $this->joinWhere($qb, new UserAdapter, 'owner',
                'id', $query['owner']['id']);
        }
        if (isset($query['vocabulary']['namespace_uri'])) {
            $this->joinWhere($qb, new VocabularyAdapter, 'vocabulary',
                'namespaceUri', $query['vocabulary']['namespace_uri']);
        }
        if (isset($query['vocabulary']['id'])) {
            $this->joinWhere($qb, new VocabularyAdapter, 'vocabulary',
                'id', $query['vocabulary']['id']);
        }
        if (isset($query['local_name'])) {
            $this->andWhere($qb, 'localName', $query['local_name']);
        }
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
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

        // Validate label
        if (null === $entity->getLabel()) {
            $errorStore->addError('label', 'The label field cannot be null.');
        }
    }
}

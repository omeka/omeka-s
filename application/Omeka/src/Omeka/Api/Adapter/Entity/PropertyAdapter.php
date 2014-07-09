<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class PropertyAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'properties';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\PropertyRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Property';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
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

    /**
     * {@inheritDoc}
     */
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

    /**
     * {@inheritDoc}
     */
    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        // Validate label
        if (null === $entity->getLabel()) {
            $errorStore->addError('label', 'The label field cannot be null.');
        }
    }
}

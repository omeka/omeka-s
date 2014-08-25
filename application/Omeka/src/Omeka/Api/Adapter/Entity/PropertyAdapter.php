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
        if (isset($data['o:owner']['o:id'])) {
            $owner = $this->getAdapter('users')
                ->findEntity($data['o:owner']['o:id']);
            $entity->setOwner($owner);
        }
        if (isset($data['o:vocabulary']['o:id'])) {
            $vocabulary = $this->getAdapter('vocabularies')
                ->findEntity($data['o:vocabulary']['o:id']);
            $entity->setVocabulary($vocabulary);
        }
        if (isset($data['o:local_name'])) {
            $entity->setLocalName($data['o:local_name']);
        }
        if (isset($data['o:label'])) {
            $entity->setLabel($data['o:label']);
        }
        if (isset($data['o:comment'])) {
            $entity->setComment($data['o:comment']);
        }

    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(array $query, QueryBuilder $qb)
    {
        if (isset($query['owner_id'])) {
            $this->joinWhere($qb, 'Omeka\Model\Entity\User', 'owner',
                'id', $query['owner_id']);
        }
        if (isset($query['vocabulary_namespace_uri'])) {
            $this->joinWhere($qb, 'Omeka\Model\Entity\Vocabulary', 'vocabulary',
                'namespaceUri', $query['vocabulary_namespace_uri']);
        }
        if (isset($query['vocabulary_id'])) {
            $this->joinWhere($qb, 'Omeka\Model\Entity\Vocabulary', 'vocabulary',
                'id', $query['vocabulary_id']);
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

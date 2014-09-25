<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ResourceClassAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = array(
        'id'         => 'id',
        'local_name' => 'localName',
        'label'      => 'label',
        'comment'    => 'comment',
    );

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'resource_classes';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\ResourceClassRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\ResourceClass';
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
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['owner_id'])) {
            $userAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Model\Entity\ResourceClass.owner',
                $userAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$userAlias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }
        if (isset($query['vocabulary_id'])) {
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Model\Entity\ResourceClass.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$vocabularyAlias.id",
                $this->createNamedParameter($qb, $query['vocabulary_id']))
            );
        }
        if (isset($query['vocabulary_namespace_uri'])) {
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Model\Entity\ResourceClass.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$vocabularyAlias.namespace_uri",
                $this->createNamedParameter($qb, $query['vocabulary_namespace_uri']))
            );
        }
        if (isset($query['vocabulary_prefix'])) {
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Model\Entity\ResourceClass.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$vocabularyAlias.prefix",
                $this->createNamedParameter($qb, $query['vocabulary_prefix']))
            );
        }
        if (isset($query['local_name'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Model\Entity\ResourceClass.localName",
                $this->createNamedParameter($qb, $query['local_name']))
            );
        }
        if (isset($query['term']) && $this->isTerm($query['term'])) {
            list($prefix, $localName) = explode(':', $query['term']);
            $vocabularyAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Model\Entity\ResourceClass.vocabulary',
                $vocabularyAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$vocabularyAlias.prefix",
                $this->createNamedParameter($qb, $prefix))
            );
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Model\Entity\ResourceClass.localName",
                $this->createNamedParameter($qb, $localName))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        // Validate local name.
        if (null === $entity->getLocalName()) {
            $errorStore->addError('local_name', 'The local_name field cannot be null.');
        }
        // Validate label.
        if (null === $entity->getLabel()) {
            $errorStore->addError('label', 'The label field cannot be null.');
        }
    }
}

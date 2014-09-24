<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class VocabularyAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = array(
        'id'            => 'id',
        'namespace_uri' => 'namespaceUri',
        'prefix'        => 'prefix',
        'label'         => 'label',
        'comment'       => 'comment',
    );

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'vocabularies';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\VocabularyRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Vocabulary';
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
        if (isset($data['o:namespace_uri'])) {
            $entity->setNamespaceUri($data['o:namespace_uri']);
        }
        if (isset($data['o:prefix'])) {
            $entity->setPrefix($data['o:prefix']);
        }
        if (isset($data['o:label'])) {
            $entity->setLabel($data['o:label']);
        }
        if (isset($data['o:comment'])) {
            $entity->setComment($data['o:comment']);
        }
        if (isset($data['o:classes']) && is_array($data['o:classes'])) {
            $resourceClassAdapter = $this->getAdapter('resource_classes');
            $resourceClassEntityClass = $resourceClassAdapter->getEntityClass();
            foreach ($data['o:classes'] as $classData) {
                if (isset($classData['o:id'])) {
                    continue; // do not process existing classes
                }
                $resourceClass = new $resourceClassEntityClass;
                $resourceClassAdapter->hydrateEntity(
                    'create', $classData, $resourceClass, $errorStore
                );
                $entity->addResourceClass($resourceClass);
            }
        }
        if (isset($data['o:properties']) && is_array($data['o:properties'])) {
            $propertyAdapter = $this->getAdapter('properties');
            $propertyEntityClass = $propertyAdapter->getEntityClass();
            foreach ($data['o:properties'] as $propertyData) {
                if (isset($propertyData['o:id'])) {
                    continue; // do not process existing properties
                }
                $property = new $propertyEntityClass;
                $propertyAdapter->hydrateEntity(
                    'create', $propertyData, $property, $errorStore
                );
                $entity->addProperty($property);
            }
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
                'Omeka\Model\Entity\Vocabulary.owner',
                $userAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$userAlias.id",
                $this->createNamedParameter($qb, $query['owner_id']))
            );
        }
        if (isset($query['namespace_uri'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Model\Entity\Vocabulary.namespaceUri",
                $this->createNamedParameter($qb, $query['namespace_uri']))
            );
        }
        if (isset($query['prefix'])) {
            $qb->andWhere($qb->expr()->eq(
                "Omeka\Model\Entity\Vocabulary.prefix",
                $this->createNamedParameter($qb, $query['prefix']))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        if (null === $entity->getNamespaceUri()) {
            $errorStore->addError('namespace_uri', 'The namespace_uri field cannot be null.');
        }
        if (null === $entity->getPrefix()) {
            $errorStore->addError('prefix', 'The prefix field cannot be null.');
        }
        if (null === $entity->getLabel()) {
            $errorStore->addError('label', 'The label field cannot be null.');
        }
    }
}

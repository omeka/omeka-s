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
        ErrorStore $errorStore, $isManaged
    ) {
        $this->hydrateOwner($data, $entity, $isManaged);

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
                $resourceClass->setVocabulary($entity);
                $resourceClassAdapter->hydrateEntity(
                    'create', $classData, $resourceClass, $errorStore
                );
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
                $property->setVocabulary($entity);
                $propertyAdapter->hydrateEntity(
                    'create', $propertyData, $property, $errorStore
                );
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
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore,
        $isManaged
    ) {
        // Validate namespace URI
        $namespaceUri = $entity->getNamespaceUri();
        if (empty($namespaceUri)) {
            $errorStore->addError('o:namespace_uri', 'The namespace URI cannot be empty.');
        }
        if (!$this->isUnique($entity, array('namespaceUri' => $namespaceUri))) {
            $errorStore->addError('o:namespace_uri', sprintf(
                'The namespace URI "%s" is already taken.',
                $namespaceUri
            ));
        }

        // Validate prefix
        $prefix = $entity->getPrefix();
        if (empty($prefix)) {
            $errorStore->addError('o:prefix', 'The prefix cannot be empty.');
        }
        if (!$this->isUnique($entity, array('prefix' => $prefix))) {
            $errorStore->addError('o:prefix', sprintf(
                'The prefix "%s" is already taken.',
                $prefix
            ));
        }

        // Validate label
        $label = $entity->getLabel();
        if (empty($label)) {
            $errorStore->addError('o:label', 'The label cannot be empty.');
        }

        // Check for uniqueness of resource class local names.
        $uniqueLocalNames = array();
        foreach ($entity->getResourceClasses() as $resourceClass) {
            if (in_array($resourceClass->getLocalName(), $uniqueLocalNames)) {
                $errorStore->addError('o:resource_class', sprintf(
                    'The local name "%s" is already taken.',
                    $resourceClass->getLocalName()
                ));
            } else {
                $uniqueLocalNames[] = $resourceClass->getLocalName();
            }
        }

        // Check for uniqueness of property local names.
        $uniqueLocalNames = array();
        foreach ($entity->getProperties() as $property) {
            if (in_array($property->getLocalName(), $uniqueLocalNames)) {
                $errorStore->addError('o:resource_class', sprintf(
                    'The local name "%s" is already taken.',
                    $property->getLocalName()
                ));
            } else {
                $uniqueLocalNames[] = $property->getLocalName();
            }
        }
    }
}

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
        if (isset($data['owner']['id'])) {
            $owner = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\User')
                ->find($data['owner']['id']);
            $entity->setOwner($owner);
        }
        if (isset($data['namespace_uri'])) {
            $entity->setNamespaceUri($data['namespace_uri']);
        }
        if (isset($data['prefix'])) {
            $entity->setPrefix($data['prefix']);
        }
        if (isset($data['label'])) {
            $entity->setLabel($data['label']);
        }
        if (isset($data['comment'])) {
            $entity->setComment($data['comment']);
        }
        if (isset($data['classes']) && is_array($data['classes'])) {
            $resourceClassAdapter = $this->getServiceLocator()
                ->get('Omeka\ApiAdapterManager')
                ->get('resource_classes');
            foreach ($data['classes'] as $classData) {
                if (isset($classData['id'])) {
                    $resourceClass = $resourceClassAdapter->findEntity(array(
                        'id' => $classData['id'],
                        'vocabulary' => $entity->getId(),
                    ));
                    $resourceClassAdapter->hydrateEntity(
                        'update', $classData, $resourceClass, $errorStore
                    );
                } else {
                    $resourceClassEntityClass = $resourceClassAdapter->getEntityClass();
                    $resourceClass = new $resourceClassEntityClass;
                    $resourceClassAdapter->hydrateEntity(
                        'create', $classData, $resourceClass, $errorStore
                    );
                    $entity->addResourceClass($resourceClass);
                }
            }
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
        if (isset($query['namespace_uri'])) {
            $this->andWhere($qb, 'namespaceUri', $query['namespace_uri']);
        }
        if (isset($query['prefix'])) {
            $this->andWhere($qb, 'prefix', $query['prefix']);
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

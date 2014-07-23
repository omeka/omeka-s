<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Stdlib\ErrorStore;

class MediaAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'media';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\MediaRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Media';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if (isset($data['owner']['id'])) {
            $owner = $this->getAdapter('users')
                ->findEntity($data['owner']['id']);
            $entity->setOwner($owner);
        }
        if (isset($data['resource_class']['id'])) {
            $resourceClass = $this->getAdapter('resource_classes')
                ->findEntity($data['resource_class']['id']);
            $entity->setResourceClass($resourceClass);
        }
        if (isset($data['type'])) {
            $entity->setType($data['type']);
        }
        if (isset($data['data'])) {
            $entity->setData($data['data']);
        }
        $valueHydrator = new ValueHydrator($this);
        $valueHydrator->hydrate($data, $entity);
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(array $query, QueryBuilder $qb)
    {}

    /**
     * {@inheritDoc}
     */
    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {}
}

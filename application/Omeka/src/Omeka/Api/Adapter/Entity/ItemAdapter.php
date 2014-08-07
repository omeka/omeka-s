<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Stdlib\ErrorStore;

class ItemAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'items';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\ItemRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Item';
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
        if (isset($data['o:resource_class']['o:id'])) {
            $resourceClass = $this->getAdapter('resource_classes')
                ->findEntity($data['o:resource_class']['o:id']);
            $entity->setResourceClass($resourceClass);
        }
        if (isset($data['o:media']) && is_array($data['o:media'])) {
            $mediaAdapter = $this->getAdapter('media');
            foreach ($data['o:media'] as $mediaData) {
                if (isset($mediaData['o:id'])) {
                    continue; // do not process existing media
                }
                $mediaEntityClass = $mediaAdapter->getEntityClass();
                $media = new $mediaEntityClass;
                $mediaAdapter->hydrateEntity(
                    'create', $mediaData, $media, $errorStore
                );
                $entity->addMedia($media);
            }
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

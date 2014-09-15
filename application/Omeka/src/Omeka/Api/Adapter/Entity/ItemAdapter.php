<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Stdlib\ErrorStore;

class ItemAdapter extends AbstractResourceEntityAdapter
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
        $this->hydrateValues($data, $entity);

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
            $mediaEntityClass = $mediaAdapter->getEntityClass();
            foreach ($data['o:media'] as $mediaData) {
                if (isset($mediaData['o:id'])) {
                    continue; // do not process existing media
                }
                $media = new $mediaEntityClass;
                $mediaAdapter->hydrateEntity(
                    'create', $mediaData, $media, $errorStore
                );
                $entity->addMedia($media);
            }
        }

        if (isset($data['o:item_set']) && is_array($data['o:item_set'])) {
            $setAdapter = $this->getAdapter('item_sets');
            $sets = $entity->getItemSets();
            $setsToAdd = array();
            $setsToRemove = clone $sets;
            foreach ($data['o:item_set'] as $itemSetData) {
                if (!isset($itemSetData['o:id'])) {
                    continue; // skip any sets with no ID
                }
                $setId = $itemSetData['o:id'];
                if (isset($sets[$setId])) {
                    $setsToRemove->remove($id);
                } else {
                    $setsToAdd[] = $setId;
                }
            }
            foreach ($setsToAdd as $setId) {
                $newSet = $setAdapter->findEntity($setId);
                $sets->add($newSet);
            }
            foreach ($setsToRemove as $setId => $set) {
                $sets->remove($setId);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        parent::buildQuery($qb, $query);

        if (isset($query['resource_class_label'])) {
            $placeholder = $this->getPlaceholder();
            $qb->innerJoin(
                'Omeka\Model\Entity\Item.resourceClass',
                'Omeka\Model\Entity\ResourceClass'
            )->andWhere($qb->expr()->eq(
                'Omeka\Model\Entity\ResourceClass.label',
                ":$placeholder"
            ))->setParameter(
                $placeholder,
                $query['resource_class_label']
            );
        }
    }
}

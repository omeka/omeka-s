<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Stdlib\ErrorStore;

class ItemAdapter extends AbstractResourceEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = array(
        'id'           => 'id',
        'is_public'    => 'isPublic',
        'created'      => 'created',
        'modified'     => 'modified',
    );

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
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        parent::buildQuery($qb, $query);

        if (isset($query['item_set_id']) && is_numeric($query['item_set_id'])) {
            $itemSetAlias = $this->createAlias();
            $qb->innerJoin(
                $this->getEntityClass() . '.itemSets',
                $itemSetAlias
            );
            $qb->andWhere($qb->expr()->eq(
                "$itemSetAlias.id",
                $this->createNamedParameter($qb, $query['item_set_id']))
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        parent::hydrate($request, $entity, $errorStore);

        $data = $request->getContent();

        // o:item_set
        if (array_key_exists('o:item_set', $data) && is_array($data['o:item_set'])) {

            $itemSetAdapter = $this->getAdapter('item_sets');
            $itemSets = $entity->getItemSets();
            $itemSetsToRetain = array();

            foreach ($data['o:item_set'] as $itemSetData) {
                if (is_array($itemSetData)
                    && array_key_exists('o:id', $itemSetData)
                    && is_numeric($itemSetData['o:id'])
                ) {
                    $itemSetId = $itemSetData['o:id'];
                } else if (is_numeric($itemSetData)) {
                    $itemSetId = $itemSetData;
                } else {
                    continue;
                }

                if (!$itemSet = $itemSets->get($itemSetId)) {
                    // Item set not already assigned. Assign it.
                    $itemSet = $itemSetAdapter->findEntity($itemSetId);
                    $itemSets->add($itemSet);
                }

                $itemSetsToRetain[] = $itemSet;
            }

            // Unassign item sets that were not included in the passed data.
            foreach ($itemSets as $itemSet) {
                if (!in_array($itemSet, $itemSetsToRetain)) {
                    $itemSets->removeElement($itemSet);
                }
            }
        }

        if (isset($data['o:media']) && is_array($data['o:media'])) {
            $mediaAdapter = $this->getAdapter('media');
            $mediaEntityClass = $mediaAdapter->getEntityClass();
            foreach ($data['o:media'] as $mediaData) {
                if (isset($mediaData['o:id'])) {
                    continue; // do not process existing media
                }
                $media = new $mediaEntityClass;
                $media->setItem($entity);
                $subrequest = new Request(Request::CREATE, 'media');
                $subrequest->setContent($mediaData);
                $subrequest->setFileData($request->getFileData());
                $mediaAdapter->hydrateEntity($subrequest, $media, $errorStore);
                $entity->getMedia()->add($media);
            }
        }
    }
}

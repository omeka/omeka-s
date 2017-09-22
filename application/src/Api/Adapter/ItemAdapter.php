<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ItemAdapter extends AbstractResourceEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'is_public' => 'isPublic',
        'created' => 'created',
        'modified' => 'modified',
    ];

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
        return 'Omeka\Api\Representation\ItemRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\Item';
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        parent::buildQuery($qb, $query);

        if (isset($query['id'])) {
            $qb->andWhere($qb->expr()->eq('Omeka\Entity\Item.id', $query['id']));
        }

        if (isset($query['item_set_id'])) {
            $itemSets = $query['item_set_id'];
            if (!is_array($itemSets)) {
                $itemSets = [$itemSets];
            }
            $itemSets = array_filter($itemSets, 'is_numeric');

            if ($itemSets) {
                $itemSetAlias = $this->createAlias();
                $qb->innerJoin(
                    $this->getEntityClass() . '.itemSets',
                    $itemSetAlias, 'WITH',
                    $qb->expr()->in("$itemSetAlias.id", $this->createNamedParameter($qb, $itemSets))
                );
            }
        }

        if (!empty($query['site_id'])) {
            $siteAdapter = $this->getAdapter('sites');
            try {
                $site = $siteAdapter->findEntity($query['site_id']);
                $params = $site->getItemPool();
                if (!is_array($params)) {
                    $params = [];
                }
                // Avoid potential infinite recursion
                unset($params['site_id']);

                $this->buildQuery($qb, $params);
            } catch (Exception\NotFoundException $e) {
                $site = null;
            }

            if (isset($query['site_attachments_only']) && $query['site_attachments_only']) {
                $siteBlockAttachmentsAlias = $this->createAlias();
                $qb->innerJoin(
                    'Omeka\Entity\Item.siteBlockAttachments',
                    $siteBlockAttachmentsAlias
                );
                $sitePageBlockAlias = $this->createAlias();
                $qb->innerJoin(
                    "$siteBlockAttachmentsAlias.block",
                    $sitePageBlockAlias
                );
                $sitePageAlias = $this->createAlias();
                $qb->innerJoin(
                    "$sitePageBlockAlias.page",
                    $sitePageAlias
                );
                $siteAlias = $this->createAlias();
                $qb->innerJoin(
                    "$sitePageAlias.site",
                    $siteAlias
                );
                $qb->andWhere($qb->expr()->eq(
                    "$siteAlias.id",
                    $this->createNamedParameter($qb, $query['site_id']))
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (array_key_exists('o:item_set', $data)
            && !is_array($data['o:item_set'])
        ) {
            $errorStore->addError('o:item_set', 'Item sets must be an array'); // @translate
        }

        if (array_key_exists('o:media', $data)
            && !is_array($data['o:media'])
        ) {
            $errorStore->addError('o:item_set', 'Media must be an array'); // @translate
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        parent::hydrate($request, $entity, $errorStore);

        $isUpdate = Request::UPDATE === $request->getOperation();
        $isPartial = $isUpdate && $request->getOption('isPartial');
        $append = $isPartial && 'append' === $request->getOption('collectionAction');
        $remove = $isPartial && 'remove' === $request->getOption('collectionAction');

        if ($this->shouldHydrate($request, 'o:item_set')) {
            $itemSetsData = $request->getValue('o:item_set', []);
            $itemSetAdapter = $this->getAdapter('item_sets');
            $itemSets = $entity->getItemSets();
            $itemSetsToRetain = [];

            foreach ($itemSetsData as $itemSetData) {
                if (is_array($itemSetData) && isset($itemSetData['o:id'])) {
                    $itemSetId = $itemSetData['o:id'];
                } elseif (is_numeric($itemSetData)) {
                    $itemSetId = $itemSetData;
                } else {
                    continue;
                }
                $itemSet = $itemSets->get($itemSetId);
                if ($remove) {
                    if ($itemSet) {
                        $itemSets->removeElement($itemSet);
                    }
                    continue;
                }
                if (!$itemSet) {
                    // Assign item set that was not already assigned.
                    $itemSet = $itemSetAdapter->findEntity($itemSetId);
                    $itemSets->add($itemSet);
                }
                $itemSetsToRetain[] = $itemSet;
            }

            if (!$append && !$remove) {
                // Remove item sets that were not included in the passed data.
                foreach ($itemSets as $itemSet) {
                    if (!in_array($itemSet, $itemSetsToRetain)) {
                        $itemSets->removeElement($itemSet);
                    }
                }
            }
        }

        if ($this->shouldHydrate($request, 'o:media')) {
            $mediasData = $request->getValue('o:media', []);
            $adapter = $this->getAdapter('media');
            $class = $adapter->getEntityClass();
            $retainMedia = [];
            $position = 1;
            foreach ($mediasData as $mediaData) {
                $subErrorStore = new ErrorStore;
                if (isset($mediaData['o:id'])) {
                    $media = $adapter->findEntity($mediaData['o:id']);
                    $media->setPosition($position);
                    if (isset($mediaData['o:is_public'])) {
                        $media->setIsPublic($mediaData['o:is_public']);
                    }
                    $retainMedia[] = $media;
                } else {
                    // Create a new media.
                    $media = new $class;
                    $media->setItem($entity);
                    $media->setPosition($position);
                    $subrequest = new Request(Request::CREATE, 'media');
                    $subrequest->setContent($mediaData);
                    $subrequest->setFileData($request->getFileData());
                    try {
                        $adapter->hydrateEntity($subrequest, $media, $subErrorStore);
                    } catch (Exception\ValidationException $e) {
                        $errorStore->mergeErrors($e->getErrorStore(), 'o:media');
                    }
                    $entity->getMedia()->add($media);
                    $retainMedia[] = $media;
                }
                $position++;
            }
            // Remove media not included in request.
            foreach ($entity->getMedia() as $media) {
                if (!in_array($media, $retainMedia, true)) {
                    $entity->getMedia()->removeElement($media);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $rawData = $request->getContent();
        $data = parent::preprocessBatchUpdate($data, $request);

        if (isset($rawData['o:item_set'])) {
            $data['o:item_set'] = $rawData['o:item_set'];
        }

        return $data;
    }
}

<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ItemAdapter extends AbstractResourceEntityAdapter
{
    protected $sortFields = [
        'id' => 'id',
        'is_public' => 'isPublic',
        'created' => 'created',
        'modified' => 'modified',
        'title' => 'title',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'title' => 'title',
        'created' => 'created',
        'modified' => 'modified',
        'is_public' => 'isPublic',
        'thumbnail' => 'thumbnail',
        'owner' => 'owner',
        'resource_class' => 'resourceClass',
        'resource_template' => 'resourceTemplate',
    ];

    public function getResourceName()
    {
        return 'items';
    }

    public function getRepresentationClass()
    {
        return \Omeka\Api\Representation\ItemRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Omeka\Entity\Item::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        parent::buildQuery($qb, $query);

        if (isset($query['item_set_id'])) {
            $itemSets = $query['item_set_id'];
            if (!is_array($itemSets)) {
                $itemSets = [$itemSets];
            }
            $itemSets = array_filter($itemSets, 'is_numeric');

            if ($itemSets) {
                $itemSetAlias = $this->createAlias();
                $qb->innerJoin(
                    'omeka_root.itemSets',
                    $itemSetAlias, 'WITH',
                    $qb->expr()->in("$itemSetAlias.id", $this->createNamedParameter($qb, $itemSets))
                );
            }
        }

        if (isset($query['site_id']) && is_numeric($query['site_id'])) {
            $siteAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.sites', $siteAlias, 'WITH', $qb->expr()->eq(
                    "$siteAlias.id",
                    $this->createNamedParameter($qb, $query['site_id'])
                )
            );

            if (isset($query['site_attachments_only']) && $query['site_attachments_only']) {
                $siteBlockAttachmentsAlias = $this->createAlias();
                $qb->innerJoin(
                    'omeka_root.siteBlockAttachments',
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
        } elseif (isset($query['in_sites']) && (is_numeric($query['in_sites']) || is_bool($query['in_sites']))) {
            $siteAlias = $this->createAlias();
            if ($query['in_sites']) {
                $qb->innerJoin('omeka_root.sites', $siteAlias);
            } else {
                $qb->leftJoin('omeka_root.sites', $siteAlias);
                $qb->andWhere($qb->expr()->isNull($siteAlias));
            }
        }
    }

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

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        parent::hydrate($request, $entity, $errorStore);

        $isCreate = Request::CREATE === $request->getOperation();
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
        if ($isCreate && !is_array($request->getValue('o:site'))) {
            // On CREATE and when no "o:site" array is passed, assign this item
            // to all sites where assignNewItems=true.
            $dql = '
                SELECT site
                FROM Omeka\Entity\Site site
                WHERE site.assignNewItems = true';
            $query = $this->getEntityManager()->createQuery($dql);
            $sites = $entity->getSites();
            foreach ($query->getResult() as $site) {
                $sites->set($site->getId(), $site);
            }
        } elseif ($this->shouldHydrate($request, 'o:site')) {
            $acl = $this->getServiceLocator()->get('Omeka\Acl');
            $sitesData = $request->getValue('o:site', []);
            $siteAdapter = $this->getAdapter('sites');
            $sites = $entity->getSites();
            $sitesToRetain = [];

            foreach ($sitesData as $siteData) {
                if (is_array($siteData) && isset($siteData['o:id'])) {
                    $siteId = $siteData['o:id'];
                } elseif (is_numeric($siteData)) {
                    $siteId = $siteData;
                } else {
                    continue;
                }
                $site = $sites->get($siteId);
                if ($remove) {
                    if ($site && $acl->userIsAllowed($site, 'can-assign-items')) {
                        $sites->removeElement($site);
                    }
                    continue;
                }
                if (!$site) {
                    // Assign site that was not already assigned.
                    $site = $siteAdapter->findEntity($siteId);
                    if ($acl->userIsAllowed($site, 'can-assign-items')) {
                        $sites->set($site->getId(), $site);
                    }
                }
                $sitesToRetain[] = $site;
            }

            if (!$append && !$remove) {
                // Remove sites that were not included in the passed data.
                foreach ($sites as $site) {
                    if (!in_array($site, $sitesToRetain) && $acl->userIsAllowed($site, 'can-assign-items')) {
                        $sites->removeElement($site);
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

    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $rawData = $request->getContent();
        $data = parent::preprocessBatchUpdate($data, $request);

        if (isset($rawData['o:item_set'])) {
            $data['o:item_set'] = $rawData['o:item_set'];
        }
        if (isset($rawData['o:site'])) {
            $data['o:site'] = $rawData['o:site'];
        }

        return $data;
    }

    public function getFulltextText($resource)
    {
        $texts = [];
        $texts[] = parent::getFulltextText($resource);
        // Get media text.
        $mediaAdapter = $this->getAdapter('media');
        foreach ($resource->getMedia() as $media) {
            $texts[] = $mediaAdapter->getFulltextText($media);
        }
        // Remove empty texts.
        $texts = array_filter($texts, function ($text) {
            return !is_null($text) && $text !== '';
        });
        return implode("\n", $texts);
    }
}

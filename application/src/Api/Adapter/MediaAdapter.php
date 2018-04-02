<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Media\Ingester\MutableIngesterInterface;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Item;
use Omeka\Media\Ingester\Fallback;
use Omeka\Stdlib\ErrorStore;

class MediaAdapter extends AbstractResourceEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'ingester' => 'ingester',
        'renderer' => 'renderer',
        'is_public' => 'isPublic',
        'created' => 'created',
        'modified' => 'modified',
    ];

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
        return 'Omeka\Api\Representation\MediaRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\Media';
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        parent::buildQuery($qb, $query);

        if (isset($query['id'])) {
            $qb->andWhere($qb->expr()->eq('Omeka\Entity\Media.id', $query['id']));
        }

        if (isset($query['item_id'])) {
            $qb->andWhere($qb->expr()->eq('Omeka\Entity\Media.item', $query['item_id']));
        }

        if (isset($query['site_id'])) {
            $itemAlias = $this->createAlias();
            $qb->innerJoin(
                'Omeka\Entity\Media.item',
                $itemAlias
            );
            $siteBlockAttachmentsAlias = $this->createAlias();
            $qb->innerJoin(
                "$itemAlias.siteBlockAttachments",
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

    /**
     * {@inheritDoc}
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()
            && !$request->getValue('o:ingester')
        ) {
            $errorStore->addError('o:ingester', 'Media must set an ingester.'); // @translate
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();

        if (Request::CREATE === $request->getOperation()) {
            // Accept the passed ingester only on CREATE to prevent overwriting
            // on subsequent UPDATE requests.
            $ingesterName = $request->getValue('o:ingester');
        } else {
            $ingesterName = $entity->getIngester();
        }
        $ingester = $this->getServiceLocator()
            ->get('Omeka\Media\Ingester\Manager')
            ->get($ingesterName);

        if (Request::CREATE === $request->getOperation()) {
            if ($ingester instanceof Fallback) {
                $errorStore->addError('o:ingester', 'Media must set a valid ingester.'); // @translate
                return;
            }
            $entity->setIngester($ingesterName);
            $entity->setRenderer($ingester->getRenderer());

            if (isset($data['o:item']['o:id'])) {
                $item = $this->getAdapter('items')
                    ->findEntity($data['o:item']['o:id']);
                $entity->setItem($item);
            }
            if (isset($data['data'])) {
                $entity->setData($data['data']);
            }
            if (isset($data['o:source'])) {
                $entity->setSource($data['o:source']);
            }
        }

        parent::hydrate($request, $entity, $errorStore);

        if ($this->shouldHydrate($request, 'o:lang')) {
            $entity->setLang($request->getValue('o:lang', null));
        }

        if (Request::CREATE === $request->getOperation()) {
            $ingester->ingest($entity, $request, $errorStore);
        } elseif ($ingester instanceof MutableIngesterInterface) {
            $ingester->update($entity, $request, $errorStore);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if (!($entity->getItem() instanceof Item)) {
            $errorStore->addError('o:item', 'Media must belong to an item.'); // @translate
        }
        parent::validateEntity($entity, $errorStore);
    }

    /**
     * {@inheritDoc}
     */
    public function hydrateOwner(Request $request, EntityInterface $entity)
    {
        if ($entity->getItem() instanceof Item) {
            $entity->setOwner($entity->getItem()->getOwner());
        }
    }

    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $rawData = $request->getContent();
        $data = parent::preprocessBatchUpdate($data, $request);

        if (array_key_exists('o:lang', $rawData)) {
            $data['o:lang'] = $rawData['o:lang'];
        }

        return $data;
    }
}

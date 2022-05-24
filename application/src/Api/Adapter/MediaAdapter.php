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
    protected $sortFields = [
        'id' => 'id',
        'ingester' => 'ingester',
        'renderer' => 'renderer',
        'is_public' => 'isPublic',
        'created' => 'created',
        'modified' => 'modified',
        'title' => 'title',
        'media_type' => 'mediaType',
        'size' => 'size',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'title' => 'title',
        'created' => 'created',
        'modified' => 'modified',
        'is_public' => 'isPublic',
        'thumbnail' => 'thumbnail',
        'ingester' => 'ingester',
        'renderer' => 'renderer',
        'data' => 'data',
        'source' => 'source',
        'media_type' => 'mediaType',
        'sha256' => 'sha256',
        'size' => 'size',
        'item' => 'item',
        'lang' => 'lang',
        'alt_text' => 'altText',
    ];

    public function getResourceName()
    {
        return 'media';
    }

    public function getRepresentationClass()
    {
        return \Omeka\Api\Representation\MediaRepresentation::class;
    }

    public function getEntityClass()
    {
        return \Omeka\Entity\Media::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        parent::buildQuery($qb, $query);

        if (isset($query['item_id']) && is_numeric($query['item_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.item',
                $this->createNamedParameter($qb, $query['item_id'])
            ));
        }

        if (!empty($query['media_type'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.mediaType',
                $this->createNamedParameter($qb, $query['media_type'])
            ));
        }

        if (!empty($query['ingester'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.ingester',
                $this->createNamedParameter($qb, $query['ingester'])
            ));
        }

        if (!empty($query['renderer'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.renderer',
                $this->createNamedParameter($qb, $query['renderer'])
            ));
        }

        if (isset($query['site_id']) && is_numeric($query['site_id'])) {
            $itemAlias = $this->createAlias();
            $qb->innerJoin(
                'omeka_root.item', $itemAlias
            );
            $siteAlias = $this->createAlias();
            $qb->innerJoin(
                "$itemAlias.sites", $siteAlias, 'WITH', $qb->expr()->eq(
                    "$siteAlias.id",
                    $this->createNamedParameter($qb, $query['site_id'])
                )
            );
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (Request::CREATE === $request->getOperation()
            && !$request->getValue('o:ingester')
        ) {
            $errorStore->addError('o:ingester', 'Media must set an ingester.'); // @translate
        }
    }

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

        if (isset($data['position']) && is_numeric($data['position'])) {
            $entity->setPosition($data['position']);
        }

        if ($this->shouldHydrate($request, 'o:lang')) {
            $entity->setLang($request->getValue('o:lang', null));
        }

        if ($this->shouldHydrate($request, 'o:alt_text')) {
            $entity->setAltText($request->getValue('o:alt_text'));
        }

        if (Request::CREATE === $request->getOperation()) {
            $ingester->ingest($entity, $request, $errorStore);
        } elseif ($ingester instanceof MutableIngesterInterface) {
            $ingester->update($entity, $request, $errorStore);
        }
    }

    public function validateEntity(EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if (!($entity->getItem() instanceof Item)) {
            $errorStore->addError('o:item', 'Media must belong to an item.'); // @translate
        }
        parent::validateEntity($entity, $errorStore);
    }

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

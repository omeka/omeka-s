<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Media\Ingester\IngesterInterface;
use Omeka\Media\Ingester\MutableIngesterInterface;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Item;
use Omeka\Entity\ResourceClass;
use Omeka\Stdlib\ErrorStore;

class MediaAdapter extends AbstractResourceEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = array(
        'id'        => 'id',
        'ingester'  => 'ingester',
        'renderer'  => 'renderer',
        'is_public' => 'isPublic',
        'created'   => 'created',
        'modified'  => 'modified',
    );

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
        if (Request::CREATE === $request->getOperation()) {
            if (!$request->getValue('o:ingester')) {
                $errorStore->addError('o:ingester', 'Media must set an ingester.');
            }
            if (!$request->getValue('o:renderer')) {
                $errorStore->addError('o:renderer', 'Media must set a renderer.');
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        $ingester = $entity->getIngester();

        if (Request::CREATE === $request->getOperation()) {
            // Accept the passed ingester and renderer only on CREATE to prevent
            // overwriting on subsequent UPDATE requests.
            $ingester = $request->getValue('o:ingester');
            $entity->setIngester($data['o:ingester']);
            $renderer = $request->getValue('o:renderer');
            $entity->setRenderer($data['o:renderer']);

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

        $ingester = $this->getServiceLocator()
            ->get('Omeka\MediaIngesterManager')->get($ingester);
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
            $errorStore->addError('o:item', 'Media must belong to an item.');
        }
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
}

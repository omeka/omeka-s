<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Media\Handler\MutableHandlerInterface;
use Omeka\Media\Handler\HandlerInterface;
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
        'type'      => 'type',
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

        if ($request->getOperation() === Request::CREATE
            && !$request->getValue('o:type')
        ) {
            $errorStore->addError('o:type', 'Media must have a type.');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        $type = $entity->getType();

        if ($request->getOperation() === Request::CREATE) {
            // Accept the passed type only on CREATE to prevent overwriting
            // on subsequent UPDATE requests.
            $type = $request->getValue('o:type');
            $entity->setType($data['o:type']);
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

        $handler = $this->getServiceLocator()
            ->get('Omeka\MediaHandlerManager')->get($type);
        if ($request->getOperation() === Request::CREATE) {
            $handler->ingest($entity, $request, $errorStore);
        } elseif ($handler instanceof MutableHandlerInterface) {
            $handler->update($entity, $request, $errorStore);
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

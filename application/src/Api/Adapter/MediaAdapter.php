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
        $type = $entity->getType();
        $data = $request->getContent();

        if ($request->getOperation() === Request::CREATE) {
            $type = $request->getValue('o:type');
            if (isset($data['o:item']['o:id'])) {
                $item = $this->getAdapter('items')
                    ->findEntity($data['o:item']['o:id']);
                $entity->setItem($item);
            }
        }
        $handler = $this->getServiceLocator()
            ->get('Omeka\MediaHandlerManager')->get($type);

        parent::hydrate($request, $entity, $errorStore);

        if ($request->getOperation() === Request::CREATE) {
            // Handle a CREATE request.
            $entity->setType($data['o:type']);
            if (isset($data['data'])) {
                $entity->setData($data['data']);
            }
            if (isset($data['o:source'])) {
                $entity->setSource($data['o:source']);
            }
            $handler->ingest($entity, $request, $errorStore);
        } elseif ($handler instanceof MutableHandlerInterface) {
            // Handle an UPDATE request if the media type is mutable.
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

<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
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

        if (!isset($data['o:type'])) {
            $errorStore->addError('o:type', 'Media must have a type.');
            return;
        }

        $handler = $this->getServiceLocator()
            ->get('Omeka\MediaHandlerManager')
            ->get($data['o:type']);
        $handler->validateRequest($request, $errorStore);
        $request->setMetadata('mediaHandler', $handler);
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        parent::hydrate($request, $entity, $errorStore);

        // Don't allow mutation of basic properties
        if ($request->getOperation() !== Request::CREATE) {
            $request->getMetadata('mediaHandler')->update($entity, $request, $errorStore);
            return;
        }

        $data = $request->getContent();

        if (isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')
                ->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }

        // If we've gotten here we're guaranteed to have a set, valid media type
        // and media handler thanks to validateRequest
        $entity->setType($data['o:type']);
        $request->getMetadata('mediaHandler')->ingest($entity, $request, $errorStore);

        if (isset($data['o:data'])) {
            $entity->setData($data['o:data']);
        }

        if (array_key_exists('o:source', $data)) {
            $entity->setSource($data['o:source']);
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
        $entity->setOwner($entity->getItem()->getOwner());
    }
}

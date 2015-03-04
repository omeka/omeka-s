<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Media\Handler\HandlerInterface;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\Item;
use Omeka\Model\Entity\ResourceClass;
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
     * @var HandlerInterface
     */
    protected $handler;

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
        return 'Omeka\Api\Representation\Entity\MediaRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Media';
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
            return;
        }

        $data = $request->getContent();

        if (isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')
                ->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }

        // If we've gotten here we're guaranteed to have a set, valid media type
        // thanks to validateRequest
        $entity->setType($data['o:type']);
        $this->getHandler()->ingest($entity, $request, $errorStore);

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
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();

        if (!isset($data['o:type'])) {
            $errorStore->addError('o:type', 'Media must have a type.');
            return;
        }

        $handler = $this->getServiceLocator()->get('Omeka\MediaManager')
            ->get($data['o:type']);
        $this->setHandler($handler);
        $handler->validateRequest($request, $errorStore);
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

    protected function setHandler(HandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    protected function getHandler()
    {
        return $this->handler;
    }
}

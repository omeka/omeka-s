<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
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
        ErrorStore $errorStore, $isManaged
    ) {
        parent::hydrate($request, $entity, $errorStore, $isManaged);

        // Don't allow mutation of basic properties
        if ($isManaged) {
            return;
        }

        $data = $request->getContent();

        if (isset($data['o:item']['o:id'])) {
            $item = $this->getAdapter('items')
                ->findEntity($data['o:item']['o:id']);
            $entity->setItem($item);
        }

        if (isset($data['o:type'])) {
            $entity->setType($data['o:type']);
        }

        if (isset($data['o:data'])) {
            $entity->setData($data['o:data']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function validateEntity(EntityInterface $entity,
        ErrorStore $errorStore, $isManaged
    ) {
        if (!($entity->getItem() instanceof Item)) {
            $errorStore->addError('o:item', 'Media must belong to an item.');
        }

        if (empty($entity->getType())) {
            $errorStore->addError('o:type', 'Media must have a type.');
        }
    }
}

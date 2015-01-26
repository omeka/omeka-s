<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Stdlib\ErrorStore;

class ItemSetAdapter extends AbstractResourceEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = array(
        'id'       => 'id',
        'created'  => 'created',
        'modified' => 'modified',
    );

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'item_sets';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\Entity\ItemSetRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\ItemSet';
    }

    /**
     * {@inheritDoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            if ('item_count' == $query['sort_by']) {
                $itemsAlias = $this->createAlias();
                $itemCountAlias = $this->createAlias();
                $qb->addSelect("COUNT($itemsAlias.id) HIDDEN $itemCountAlias")
                ->leftJoin('Omeka\Model\Entity\ItemSet.items', $itemsAlias)
                ->groupBy('Omeka\Model\Entity\ItemSet.id')
                ->orderBy($itemCountAlias, $query['sort_order']);
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(array $data, EntityInterface $entity,
        ErrorStore $errorStore, $isManaged
    ) {
        parent::hydrate($data, $entity, $errorStore, $isManaged);
    }
}

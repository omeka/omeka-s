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
                $this->sortByCount($qb, $query, 'items');
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }
}

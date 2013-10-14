<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class Item extends AbstractEntity
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Item';
    }

    public function hydrate(array $data, $entity)
    {
    }

    public function extract($entity)
    {
    }

    public function buildQuery(array $query, QueryBuilder $qb)
    {
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
    }
}

<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ModuleAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Module';
    }

    public function hydrate(array $data, $entity)
    {
        if (isset($data['id'])) {
            $entity->setId($data['id']);
        }
        if (isset($data['is_active'])) {
            $entity->setIsActive($data['is_active']);
        }
    }

    public function extract($entity)
    {
        return array(
            'id' => $entity->getId(),
            'is_active' => $entity->getIsActive(),
        );
    }

    public function buildQuery(array $query, QueryBuilder $qb)
    {
        if (isset($query['is_active'])) {
            $this->andWhere($qb, 'isActive', $query['is_active']);
        }
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {}
}

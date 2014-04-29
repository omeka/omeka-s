<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\ResourceClass;
use Omeka\Stdlib\ErrorStore;

class ItemAdapter extends AbstractResourceAdapter
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Item';
    }

    public function hydrate(array $data, $entity)
    {
        if (isset($data['owner']['id'])) {
            $owner = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\User')
                ->find($data['owner']['id']);
            $entity->setOwner($owner);
        }
        if (isset($data['resource_class']['id'])) {
            $resourceClass = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\ResourceClass')
                ->find($data['resource_class']['id']);
            $entity->setResourceClass($resourceClass);
        }
    }

    public function extract($entity)
    {
        $item = array(
            '@id'   => $this->getApiUrl($entity),
            'id'    => $entity->getId(),
            'owner' => $this->getReference(
                $entity->getOwner(),
                $this->getAdapter('users')
            ),
            'resource_class' => $this->getReference(
                $entity->getResourceClass(),
                $this->getAdapter('resource_classes')
            ),
        );
        return array_merge($item, $this->extractValues($entity));
    }

    public function buildQuery(array $query, QueryBuilder $qb)
    {
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {}
}

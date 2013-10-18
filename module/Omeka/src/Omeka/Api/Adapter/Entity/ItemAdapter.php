<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ItemAdapter extends AbstractEntityAdapter
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
        return array(
            'id' => $entity->getId(),
            'owner' => $this->extractEntity($entity->getOwner(), new UserAdapter),
            'resource_class' => $this->extractEntity(
                $entity->getOwner(),
                new ResourceClassAdapter
            ),
        );
    }

    public function buildQuery(array $query, QueryBuilder $qb)
    {
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        if (!$entity->getResourceClass() instanceof Omeka\Model\Entity\ResourceClass) {
            $errorStore->addError('resource_class', 'The item is missing a resource class.');
        }
    }
}

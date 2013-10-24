<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class ValueAdapter extends AbstractEntityAdapter
{
    public function getEntityClass()
    {
        return 'Omeka\Model\Entity\Value';
    }

    public function hydrate(array $data, $entity)
    {
        if (isset($data['owner']['id'])) {
            $owner = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\User')
                ->find($data['owner']['id']);
            $entity->setOwner($owner);
        }
        if (isset($data['resource']['id'])) {
            $resource = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\Resource')
                ->find($data['resource']['id']);
            $entity->setResource($resource);
        }
        if (isset($data['property']['id'])) {
            $property = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\Property')
                ->find($data['property']['id']);
            $entity->setProperty($property);
        }
        if (isset($data['type'])) {
            $entity->setType($data['type']);
        }
        if (isset($data['value'])) {
            $entity->setValue($data['value']);
        }
        if (isset($data['value_transformed'])) {
            $entity->setValueTransformed($data['value_transformed']);
        }
        if (isset($data['lang'])) {
            $entity->setLang($data['lang']);
        }
        if (isset($data['is_html'])) {
            $entity->setIsHtml($data['is_html']);
        }
        if (isset($data['value_resource']['id'])) {
            $valueResource = $this->getEntityManager()
                ->getRepository('Omeka\Model\Entity\Resource')
                ->find($data['value_resource']['id']);
            $entity->setValueResource($valueResource);
        }
    }

    /**
     * @todo We need a way to get the entity adapter when we only know about the
     * entity. That way, we can extract inverse record association.
     */
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

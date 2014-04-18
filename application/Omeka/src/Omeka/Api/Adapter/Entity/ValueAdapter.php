<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Entity\Property;
use Omeka\Model\Entity\Resource;
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

    public function extract($entity)
    {
        return array(
            '@id'     => $this->getApiUrl($entity),
            'id'      => $entity->getId(),
            'type'    => $entity->getType(),
            'lang'    => $entity->getLang(),
            'is_html' => $entity->getIsHtml(),
            'value'   => $entity->getValue(),
            'value_transformed' => $entity->getValueTransformed(),
            'value_resource' => $this->getReference(
                $entity->getValueResource(),
                $this->getAdapter($entity->getValueResource()->getResourceName())
            ),
            'resource' => $this->getReference(
                $entity->getResource(),
                $this->getAdapter($entity->getResource()->getResourceName())
            ),
            'property' => $this->getReference(
                $entity->getProperty(),
                $this->getAdapter('properties')
            ),
            'owner' => $this->getReference(
                $entity->getOwner(),
                $this->getAdapter('users')
            ),
        );
    }

    public function buildQuery(array $query, QueryBuilder $qb)
    {
        $entityClass = $this->getEntityClass();
        if (isset($query['resource']['id'])) {
            $this->andWhere($qb, 'resource', $query['resource']['id']);
        }
        if (isset($query['property']['id'])) {
            $this->andWhere($qb, 'property', $query['property']['id']);
        }
        if (isset($query['type'])) {
            $this->andWhere($qb, 'type', $query['type']);
        }
        if (isset($query['lang'])) {
            $this->andWhere($qb, 'lang', $query['lang']);
        }
    }

    public function validate(EntityInterface $entity, ErrorStore $errorStore,
        $isPersistent
    ) {
        // Validate resource
        if (!$entity->getResource() instanceof Resource) {
            $errorStore->addError('resource', 'The resource must be an instance of Omeka\Model\Entity\Resource.');
        }

        // Validate property
        if (!$entity->getProperty() instanceof Property) {
            $errorStore->addError('resource', 'The resource must be an instance of Omeka\Model\Entity\Property.');
        }

        // Validate type
        if (!in_array($entity->getType(), $entity->getValidTypes())) {
            $errorStore->addError('label', 'The provided value type is invalid.');
        }

        // Validate value resource
        if (null !== $entity->getValueResource()
            && !$entity->getValueResource() instanceof Resource
        ) {
            $errorStore->addError('resource', 'The value resource must be an instance of Omeka\Model\Entity\Resource.');
        }
    }
}

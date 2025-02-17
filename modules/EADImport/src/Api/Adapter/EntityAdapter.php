<?php
namespace EADImport\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use EADImport\Api\Representation\EntityRepresentation;
use EADImport\Entity\EADImportEntity;

class EntityAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'eadimport_entities';
    }

    public function getEntityClass()
    {
        return EADImportEntity::class;
    }

    public function getRepresentationClass()
    {
        return EntityRepresentation::class;
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['job_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.job',
                $this->createNamedParameter($qb, $query['job_id']))
            );
        }
        if (isset($query['site_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.site',
                $this->createNamedParameter($qb, $query['site_id']))
            );
        }
        if (isset($query['entity_id'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.entity_id',
                $this->createNamedParameter($qb, $query['entity_id']))
            );
        }
        if (isset($query['resource_type'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.resource_type',
                $this->createNamedParameter($qb, $query['resource_type']))
            );
        }
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();
        if (isset($data['o:job']['o:id'])) {
            $job = $this->getAdapter('jobs')->findEntity($data['o:job']['o:id']);
            $entity->setJob($job);
        }

        if (isset($data['o:site']['o:id'])) {
            $site = $this->getAdapter('sites')->findEntity($data['o:site']['o:id']);
            $entity->setSite($site);
        }

        if (isset($data['entity_id'])) {
            $entity->setEntityId($data['entity_id']);
        }

        if (isset($data['resource_type'])) {
            $entity->setResourceType($data['resource_type']);
        }
    }
}

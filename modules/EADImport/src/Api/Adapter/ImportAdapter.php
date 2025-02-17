<?php
namespace EADImport\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use EADImport\Api\Representation\ImportRepresentation;
use EADImport\Entity\EADImportImport;

class ImportAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'eadimport_imports';
    }

    public function getRepresentationClass()
    {
        return ImportRepresentation::class;
    }

    public function getEntityClass()
    {
        return EADImportImport::class;
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

        if (isset($data['resource_type'])) {
            $entity->setResourceType($data['resource_type']);
        }

        if (isset($data['name'])) {
            $entity->setName($data['name']);
        }

        if (isset($data['mapping'])) {
            $entity->setMapping($data['mapping']);
        }
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

        if (isset($query['resource_type'])) {
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.resource_type',
                $this->createNamedParameter($qb, $query['resource_type']))
            );
        }
    }
}

<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class JobAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'status' => 'status',
        'class' => 'class',
        'started' => 'started',
        'ended' => 'ended',
    ];

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'jobs';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return 'Omeka\Api\Representation\JobRepresentation';
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return 'Omeka\Entity\Job';
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            if ('owner_email' == $query['sort_by']) {
                $entityClass = $this->getEntityClass();
                $ownerAlias = $this->createAlias();
                $qb->leftJoin("$entityClass.owner", $ownerAlias)
                    ->addOrderBy("$ownerAlias.email", $query['sort_order']);
            } else {
                parent::sortQuery($qb, $query);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['class'])) {
            $qb->andWhere($qb->expr()->eq(
                $this->getEntityClass() . ".class",
                $this->createNamedParameter($qb, $query['class']))
            );
        }
    }
}

<?php
namespace Collecting\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

class CollectingUserAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'collecting_users';
    }

    public function getRepresentationClass()
    {
        return 'Collecting\Api\Representation\CollectingUserRepresentation';
    }

    public function getEntityClass()
    {
        return 'Collecting\Entity\CollectingUser';
    }

    public function create(Request $request)
    {
        // Creation is done during CollectingItem hydration via cascade="persist"
        throw new Exception\OperationNotImplementedException(
            'CollectingUserAdapter does not implement the create operation.'
        );
    }

    public function batchCreate(Request $request)
    {
        throw new Exception\OperationNotImplementedException(
            'CollectingUserAdapter does not implement the batchCreate operation.'
        );
    }

    public function update(Request $request)
    {
        throw new Exception\OperationNotImplementedException(
            'CollectingUserAdapter does not implement the update operation.'
        );
    }

    public function delete(Request $request)
    {
        throw new Exception\OperationNotImplementedException(
            'CollectingUserAdapter does not implement the delete operation.'
        );
    }

    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
    }
}

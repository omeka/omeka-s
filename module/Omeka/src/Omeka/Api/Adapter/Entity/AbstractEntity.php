<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Response;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Exception as ModelException;
use Omeka\Stdlib\ErrorStore;
use Zend\Stdlib\Hydrator\HydratorInterface;

/**
 * Abstract entity API adapter.
 */
abstract class AbstractEntity extends AbstractAdapter implements
    EntityInterface,
    HydratorInterface
{
    /**
     * Search a set of entities.
     *
     * @param null|array $data
     * @return Response
     */
    public function search($data = null)
    {
        // Build the search query.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->from($this->getEntityClass(), $this->getEntityClass());
        $this->buildQuery($data, $qb);

        // Get total results.
        $qb->select($qb->expr()->count($this->getEntityClass() . '.id'));
        $totalResults = $qb->getQuery()->getSingleScalarResult();

        // Get the queried results.
        $qb->select($this->getEntityClass());
        $this->setOrderBy($data, $qb);
        $this->setLimitAndOffset($data, $qb);

        //~ echo $qb->getDQL();
        //~ echo "\n\n";
        //~ echo $qb->getQuery()->getSQL();
        //~ exit;

        $entities = $qb->getQuery()->getResult();
        foreach ($entities as &$entity) {
            $entity = $this->extract($entity);
        }
        $response = new Response($entities);
        $response->setTotalResults($totalResults);
        return $response;
    }

    /**
     * Create an entity.
     *
     * @param null|array $data
     * @return Response
     */
    public function create($data = null)
    {
        $response = new Response;

        $entityClass = $this->getEntityClass();
        $entity = new $entityClass;
        $this->hydrate($data, $entity);

        $errorStore = $this->validateEntity($entity);
        if ($errorStore->hasErrors()) {
            $response->setStatus(Response::ERROR_VALIDATION);
            $response->mergeErrors($errorStore);
            return $response;
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        $response->setContent($this->extract($entity));
        return $response;
    }

    /**
     * Read an entity.
     *
     * @param mixed $id
     * @param null|array $data
     * @return Response
     */
    public function read($id, $data = null)
    {
        $response = new Response;
        try {
            $entity = $this->find($id);
        } catch (ModelException\EntityNotFoundException $e) {
            $response->setStatus(Response::ERROR_NOT_FOUND);
            $response->addError(Response::ERROR_NOT_FOUND, $e->getMessage());
            return $response;
        }
        $response->setContent($this->extract($entity));
        return $response;
    }

    /**
     * Update an entity.
     *
     * @param mixed $id
     * @param null|array $data
     * @return Response
     */
    public function update($id, $data = null)
    {
        $response = new Response;
        try {
            $entity = $this->find($id);
        } catch (ModelException\EntityNotFoundException $e) {
            $response->setStatus(Response::ERROR_NOT_FOUND);
            $response->addError(Response::ERROR_NOT_FOUND, $e->getMessage());
            return $response;
        }
        $this->hydrate($data, $entity);
        $errorStore = $this->validateEntity($entity);
        if ($errorStore->hasErrors()) {
            $response->setStatus(Response::ERROR_VALIDATION);
            $response->mergeErrors($errorStore);
            // Refresh the entity from the database, overriding any local
            // changes that have not yet been persisted
            $this->getEntityManager()->refresh($entity);
            $response->setContent($this->extract($entity));
            return $response;
        }
        $this->getEntityManager()->flush();
        $response->setContent($this->extract($entity));
        return $response;
    }

    /**
     * Delete an entity.
     *
     * @param mixed $id
     * @param null|array $data
     * @return Response
     */
    public function delete($id, $data = null)
    {
        $response = new Response;
        try {
            $entity = $this->find($id);
        } catch (ModelException\EntityNotFoundException $e) {
            $response->setStatus(Response::ERROR_NOT_FOUND);
            $response->addError(Response::ERROR_NOT_FOUND, $e->getMessage());
            return $response;
        }
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
        $response->setContent($this->extract($entity));
        return $response;
    }

    /**
     * Get the entity manager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getServiceLocator()->get('EntityManager');
    }

    /**
     * Get an entity repository.
     *
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->getEntityClass());
    }

    /**
     * Find an entity by its identifier.
     *
     * @param int $id
     * @return \Omeka\Model\Entity\EntityInterface
     */
    protected function find($id)
    {
        $entity = $this->getRepository()->find($id);
        if (!$entity instanceof EntityInterface) {
            throw new ModelException\EntityNotFoundException(sprintf(
                'An "%s" entity with ID "%s" was not found',
                $this->getEntityClass(),
                $id
            ));
        }
        return $entity;
    }

    /**
     * Validate an entity.
     *
     * @param EntityInterface $entity
     * @return ErrorStore
     */
    protected function validateEntity(EntityInterface $entity)
    {
        $errorStore = new ErrorStore;
        $this->validate($entity, $errorStore, $this->entityIsPersistent($entity));
        return $errorStore;
    }

    /**
     * Check whether an entity is persistent.
     *
     * @param EntityInterface $entity
     * @return bool
     */
    protected function entityIsPersistent(EntityInterface $entity)
    {
        $entityState = $this->getEntityManager()
            ->getUnitOfWork()
            ->getEntityState($entity);
        return UnitOfWork::STATE_MANAGED === $entityState;
    }

    /**
     * Set an order by condition to the query builder.
     *
     * @param array $query
     * @param QueryBuilder $queryBuilder
     */
    protected function setOrderBy(array $query, QueryBuilder $queryBuilder)
    {
        if (!isset($query['order_by'])) {
            return;
        }
        $orderBy = $query['order_by'];
        $sort = null;
        if (isset($query['sort'])
            && in_array($query['sort'], array('ASC', 'DESC'))) {
            $sort = $query['sort'];
        }
        $queryBuilder->orderBy($this->getEntityClass() . ".$orderBy", $sort);
    }

    /**
     * Set limit (max results) and offset (first result) conditions to the
     * query builder.
     *
     * @param array $query
     * @param QueryBuilder $queryBuilder
     */
    protected function setLimitAndOffset(array $query, QueryBuilder $queryBuilder)
    {
        if (!isset($query['limit']) && !isset($query['offset'])) {
            return;
        }
        if (isset($query['limit'])) {
            $queryBuilder->setMaxResults($query['limit']);
        }
        if (isset($query['offset'])) {
            $queryBuilder->setFirstResult($query['offset']);
        }
    }
}

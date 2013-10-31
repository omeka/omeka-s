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
abstract class AbstractEntityAdapter extends AbstractAdapter implements
    EntityAdapterInterface,
    HydratorInterface
{
    /**
     * Extract properties from an entity.
     *
     * @param EntityInterface $entity
     * @return array
     */
    abstract public function extract($entity);

    /**
     * Hydrate an entity with the provided array.
     *
     * Do not modify or perform operations on the data when setting properties.
     * Validation should be done in self::validate(). Filtering should be done
     * in the entity's mutator methods.
     *
     * @param array $data
     * @param EntityInterface $entity
     */
    abstract public function hydrate(array $data, $object);

    /**
     * Search a set of entities.
     *
     * @param null|array $data
     * @return Response
     */
    public function search($data = null)
    {
        $entityClass = $this->getEntityClass();

        // Begin building the search query.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($entityClass)->from($entityClass, $entityClass);
        $this->buildQuery($data, $qb);

        // Get total results.
        $qbTotalResults = clone $qb;
        $qbTotalResults->select(
            $qbTotalResults->expr()->count("$entityClass.id")
        );
        $totalResults = $qbTotalResults->getQuery()->getSingleScalarResult();

        // Finish building the search query and get the results.
        $this->setOrderBy($data, $qb);
        $this->setLimitAndOffset($data, $qb);
        $entities = array();
        foreach ($qb->getQuery()->iterate() as $row) {
            $entities[] = $this->extract($row[0]);
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
        // Sub-requests should not be flushed.
        if (!$this->getRequest()->isSubRequest()) {
            $this->getEntityManager()->flush();
        }
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
        // Sub-requests should not be flushed.
        if (!$this->getRequest()->isSubRequest()) {
            $this->getEntityManager()->flush();
        }
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
        // Sub-requests should not be flushed.
        if (!$this->getRequest()->isSubRequest()) {
            $this->getEntityManager()->flush();
        }
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
     * Extract an entity using the provided adapter.
     *
     * Primarily used to extract inverse associations.
     *
     * @param null|EntityInterface $entity
     * @param EntityAdapterInterface $adapter
     * @return null|array
     */
    protected function extractEntity($entity, EntityAdapterInterface $adapter)
    {
        if (!$entity instanceof EntityInterface) {
            return null;
        }
        return $adapter->extract($entity);
    }

    /**
     * Set an order by condition to the query builder.
     *
     * @param array $query
     * @param QueryBuilder $queryBuilder
     */
    protected function setOrderBy(array $query, QueryBuilder $qb)
    {
        if (!isset($query['sort_by'])) {
            return;
        }
        $sortBy = $query['sort_by'];
        $sortOrder = null;
        if (isset($query['sort_order'])
            && in_array(strtoupper($query['sort_order']), array('ASC', 'DESC'))) {
            $sortOrder = strtoupper($query['sort_order']);
        }
        $qb->orderBy($this->getEntityClass() . ".$sortBy", $sortOrder);
    }

    /**
     * Set limit (max results) and offset (first result) conditions to the
     * query builder.
     *
     * @param array $query
     * @param QueryBuilder $queryBuilder
     */
    protected function setLimitAndOffset(array $query, QueryBuilder $qb)
    {
        if (!isset($query['limit']) && !isset($query['offset'])) {
            return;
        }
        if (isset($query['limit'])) {
            $qb->setMaxResults($query['limit']);
        }
        if (isset($query['offset'])) {
            $qb->setFirstResult($query['offset']);
        }
    }
}

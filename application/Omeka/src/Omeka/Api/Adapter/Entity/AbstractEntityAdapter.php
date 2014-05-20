<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Query\Expr;
use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Event\Event;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Exception as ModelException;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\DateTime;
use Zend\Stdlib\Hydrator\HydratorInterface;

/**
 * Abstract entity API adapter.
 */
abstract class AbstractEntityAdapter extends AbstractAdapter implements
    EntityAdapterInterface
{
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
     * {@inheritDoc}
     */
    public function search(Request $request)
    {
        $entityClass = $this->getEntityClass();

        // Begin building the search query.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($entityClass)->from($entityClass, $entityClass);
        $this->buildQuery($request->getContent(), $qb);

        // Trigger the search.query event.
        $event = new Event(Event::API_SEARCH_QUERY, $this, array(
            'services' => $this->getServiceLocator(),
            'request' => $request,
            'query_builder' => $qb,
        ));
        $this->getEventManager()->trigger($event);

        // Get total results.
        $qbTotalResults = clone $qb;
        $qbTotalResults->select(
            $qbTotalResults->expr()->count("$entityClass.id")
        );
        $totalResults = $qbTotalResults->getQuery()->getSingleScalarResult();

        // Finish building the search query and get the results.
        $this->setOrderBy($request->getContent(), $qb);
        $this->setLimitAndOffset($request->getContent(), $qb);
        $representations = array();
        foreach ($qb->getQuery()->iterate() as $row) {
            $representations[] = $this->getRepresentation($row[0]->getId(), $row[0]);
        }

        $response = new Response($representations);
        $response->setTotalResults($totalResults);
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Request $request)
    {
        $t = $this->getTranslator();
        $response = new Response;

        $entityClass = $this->getEntityClass();
        $entity = new $entityClass;
        $this->hydrate($request->getContent(), $entity);

        // Verify that the current user has access to create this entity.
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if (!$acl->isAllowed('current_user', $entity, 'create')) {
            throw new Exception\PermissionDeniedException(sprintf(
                $t->translate('Permission denied for the current user to create the %1$s resource.'),
                $entity->getResourceId()
            ));
        }

        // Trigger the create.validate.pre event.
        $event = new Event(Event::API_CREATE_VALIDATE_PRE, $this, array(
            'services' => $this->getServiceLocator(),
            'request' => $request,
            'entity' => $entity,
        ));
        $this->getEventManager()->trigger($event);

        $errorStore = $this->validateEntity($entity);
        if ($errorStore->hasErrors()) {
            $validationException = new Exception\ValidationException;
            $validationException->setErrorStore($errorStore);
            throw $validationException;
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        // Refresh the entity on the chance that it contains associations that
        // have not been loaded.
        $this->getEntityManager()->refresh($entity);
        $response->setContent($this->extract($entity));
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function batchCreate(Request $request)
    {
        $response = new Response;

        $entities = array();
        $entityRepresentations = array();
        foreach ($request->getContent() as $datum) {

            $entityClass = $this->getEntityClass();
            $entity = new $entityClass;
            $this->hydrate($datum, $entity);

            // Verify that the current user has access to create this entity.
            $acl = $this->getServiceLocator()->get('Omeka\Acl');
            if (!$acl->isAllowed('current_user', $entity, 'create')) {
                $response->setStatus(Response::ERROR_PERMISSION_DENIED);
                $response->addError(Response::ERROR_PERMISSION_DENIED, sprintf(
                    'Permission denied for the current user to create the %s resource.',
                    $entity->getResourceId()
                ));
                continue;
            }

            // Trigger the create.validate.pre event.
            $event = new Event(Event::API_CREATE_VALIDATE_PRE, $this, array(
                'services' => $this->getServiceLocator(),
                'request' => $request,
                'entity' => $entity,
            ));
            $this->getEventManager()->trigger($event);

            $errorStore = $this->validateEntity($entity);
            if ($errorStore->hasErrors()) {
                $response->setStatus(Response::ERROR_VALIDATION);
                $response->mergeErrors($errorStore);
                continue;
            }

            $this->getEntityManager()->persist($entity);
            $entities[] = $entity;
            $entityRepresentations[] = $this->extract($entity);
        }

        if ($response->isError()) {
            // Prevent subsequent flushes when an error has occurred.
            foreach ($entities as $entity) {
                $this->getServiceLocator()->get('Omeka\EntityManager')->detach($entity);
            }
            return $response;
        }

        $this->getEntityManager()->flush();
        $response->setContent($entityRepresentations);
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function read(Request $request)
    {
        $t = $this->getTranslator();
        $response = new Response;

        $entity = $this->getEntityManager()->find(
            $this->getEntityClass(),
            $request->getId()
        );
        if (null === $entity) {
            throw new Exception\NotFoundException(sprintf(
                $t->translate('An "%1$s" entity with ID "%2$s" was not found.'),
                $this->getEntityClass(),
                $request->getId()
            ));
        }

        // Verify that the current user has access to read this entity.
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if (!$acl->isAllowed('current_user', $entity, 'read')) {
            throw new Exception\PermissionDeniedException(sprintf(
                $t->translate('Permission denied for the current user to read the %1$s resource.'),
                $entity->getResourceId()
            ));
        }

        // Trigger the read.find.post event.
        $event = new Event(Event::API_READ_FIND_POST, $this, array(
            'services' => $this->getServiceLocator(),
            'request' => $request,
            'entity' => $entity,
        ));
        $this->getEventManager()->trigger($event);

        $response->setContent($this->extract($entity));
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Request $request)
    {
        $t = $this->getTranslator();
        $response = new Response;

        $entity = $this->getEntityManager()->find(
            $this->getEntityClass(),
            $request->getId()
        );
        if (null === $entity) {
            throw new Exception\NotFoundException(sprintf(
                $t->translate('An "%1$s" entity with ID "%2$s" was not found.'),
                $this->getEntityClass(),
                $request->getId()
            ));
        }
        $this->hydrate($request->getContent(), $entity);

        // Verify that the current user has access to update this entity.
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if (!$acl->isAllowed('current_user', $entity, 'update')) {
            throw new Exception\PermissionDeniedException(sprintf(
                $t->translate('Permission denied for the current user to update the %1$s resource.'),
                $entity->getResourceId()
            ));
        }

        // Trigger the update.validate.pre event.
        $event = new Event(Event::API_UPDATE_VALIDATE_PRE, $this, array(
            'services' => $this->getServiceLocator(),
            'request' => $request,
            'entity' => $entity,
        ));
        $this->getEventManager()->trigger($event);

        $errorStore = $this->validateEntity($entity);
        if ($errorStore->hasErrors()) {
            // Refresh the entity from the database, overriding any local
            // changes that have not yet been persisted
            $this->getEntityManager()->refresh($entity);
            $validationException = new Exception\ValidationException;
            $validationException->setErrorStore($errorStore);
            throw $validationException;
        }
        $this->getEntityManager()->flush();
        $response->setContent($this->extract($entity));
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Request $request)
    {
        $t = $this->getTranslator();
        $response = new Response;

        $entity = $this->getEntityManager()->find(
            $this->getEntityClass(),
            $request->getId()
        );
        if (null === $entity) {
            throw new Exception\NotFoundException(sprintf(
                $t->translate('An "%1$s" entity with ID "%2$s" was not found.'),
                $this->getEntityClass(),
                $request->getId()
            ));
        }

        // Verify that the current user has access to delete this entity.
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if (!$acl->isAllowed('current_user', $entity, 'delete')) {
            throw new Exception\PermissionDeniedException(sprintf(
                $t->translate('Permission denied for the current user to delete the %1$s resource.'),
                $entity->getResourceId()
            ));
        }

        // Trigger the delete.find.post event.
        $event = new Event(Event::API_DELETE_FIND_POST, $this, array(
            'services' => $this->getServiceLocator(),
            'request' => $request,
            'entity' => $entity,
        ));
        $this->getEventManager()->trigger($event);

        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
        $response->setContent($this->extract($entity));
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function getApiUrl($data)
    {
        if (!$data instanceof EntityInterface) {
            throw new Exception\InvalidArgumentException(
                $this->getTranslator()->translate(
                    'The passed resource does not implement Omeka\Model\Entity\EntityInterface.'
                )
            );
        }

        $url = $this->getServiceLocator()->get('ViewHelperManager')->get('Url');
        return $url(
            'api/default',
            array('resource' => $this->getResourceName(), 'id' => $data->getId()),
            array('force_canonical' => true)
        );
    }

    /**
     * Get the entity manager.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->getServiceLocator()->get('Omeka\EntityManager');
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
     * @param QueryBuilder $qb
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
     * @param QueryBuilder $qb
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

    /**
     * Add a simple where clause to query a field.
     *
     * @param QueryBuilder $qb
     * @param string $whereField The field to query
     * @param string $whereValue The value to query
     */
    protected function andWhere(QueryBuilder $qb, $whereField, $whereValue)
    {
        $qb->andWhere($qb->expr()->eq(
            $this->getEntityClass() . ".$whereField", ":$whereField"
        ))->setParameter($whereField, $whereValue);
    }

    /**
     * Add a simple where clause (using inner join) to a query for a many-to-one
     * association.
     *
     * @param QueryBuilder $qb
     * @param EntityAdapterInterface $targetEntityAdapter
     * @param string $targetEntityField The target entity field on the root
     * entity declaring the many-to-one association
     * @param string $whereField The target entity field to query
     * @param string $whereValue The value to query
     */
    protected function joinWhere(QueryBuilder $qb,
        EntityAdapterInterface $targetEntityAdapter, $targetEntityField,
        $whereField, $whereValue
    ) {
        $rootEntityClass = $this->getEntityClass();
        $targetEntityClass = $targetEntityAdapter->getEntityClass();
        $alias = "{$targetEntityField}_{$whereField}";

        // Get all joined entities from the query builder and check whether the
        // target entity is already joined. A duplicate joined entity would
        // raise an error when making the query.
        $joinEntityClasses = array();
        $joins = $qb->getDQLPart('join');
        if (isset($joins[$rootEntityClass])) {
            foreach ($joins[$rootEntityClass] as $join) {
                $joinEntityClasses[] = $join->getJoin();
            }
        }

        if (!in_array($targetEntityClass, $joinEntityClasses)) {
            $qb->addSelect($targetEntityClass)
                ->innerJoin(
                    $targetEntityClass,
                    $targetEntityClass,
                    Expr\Join::WITH,
                    $qb->expr()->eq(
                        "$rootEntityClass.$targetEntityField",
                        "$targetEntityClass.id"
                    )
                );
        }
        $qb->andWhere($qb->expr()->eq(
            "$targetEntityClass.$whereField",
            ":$alias")
        )->setParameter($alias, $whereValue);
    }
}

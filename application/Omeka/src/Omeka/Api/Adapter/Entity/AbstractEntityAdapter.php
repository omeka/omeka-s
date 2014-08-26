<?php
namespace Omeka\Api\Adapter\Entity;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Tools\Pagination\Paginator;
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
     * An index for query builder aliases to ensure uniqueness.
     *
     * @var int
     */
    protected $aliasIndex = 0;

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
            'query_builder' => $qb,
        ));
        $this->getEventManager()->trigger($event);

        // Finish building the search query and get the representations.
        $this->setOrderBy($request->getContent(), $qb);
        $this->setLimitAndOffset($request->getContent(), $qb);
        $paginator = new Paginator($qb);
        $representations = array();
        foreach ($paginator as $entity) {
            $representations[] = $this->getRepresentation($entity->getId(), $entity);
        }

        $response = new Response($representations);
        $response->setTotalResults($paginator->count());
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
        $this->hydrateEntity(
            Request::CREATE,
            $request->getContent(),
            $entity,
            new ErrorStore
        );
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        // Refresh the entity on the chance that it contains associations that
        // have not been loaded.
        $this->getEntityManager()->refresh($entity);
        $representation = $this->getRepresentation($entity->getId(), $entity);
        $response->setContent($representation);
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function batchCreate(Request $request)
    {
        $response = new Response;

        $errorStore = new ErrorStore;
        $representations = array();
        foreach ($request->getContent() as $datum) {
            $entityClass = $this->getEntityClass();
            $entity = new $entityClass;
            $this->hydrateEntity(Request::CREATE, $datum, $entity, $errorStore);
            $this->getEntityManager()->persist($entity);
            $representations[] = $this->getRepresentation($entity->getId(), $entity);
        }

        $this->getEntityManager()->flush();
        $response->setContent($representations);
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function read(Request $request)
    {
        $t = $this->getTranslator();
        $response = new Response;

        $entity = $this->findEntity(array('id' => $request->getId()));
        $this->authorize($entity, Request::READ);

        // Trigger the read.find.post event.
        $event = new Event(Event::API_READ_FIND_POST, $this, array(
            'services' => $this->getServiceLocator(),
            'entity' => $entity,
        ));
        $this->getEventManager()->trigger($event);

        $representation = $this->getRepresentation($entity->getId(), $entity);
        $response->setContent($representation);
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function update(Request $request)
    {
        $t = $this->getTranslator();
        $response = new Response;

        $entity = $this->findEntity(array('id' => $request->getId()));
        $this->hydrateEntity(
            Request::UPDATE,
            $request->getContent(),
            $entity,
            new ErrorStore
        );
        $this->getEntityManager()->flush();
        $representation = $this->getRepresentation($entity->getId(), $entity);
        $response->setContent($representation);
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Request $request)
    {
        $t = $this->getTranslator();
        $response = new Response;

        $entity = $this->findEntity(array('id' => $request->getId()));
        $this->authorize($entity, Request::DELETE);

        // Trigger the delete.find.post event.
        $event = new Event(Event::API_DELETE_FIND_POST, $this, array(
            'services' => $this->getServiceLocator(),
            'entity' => $entity,
        ));
        $this->getEventManager()->trigger($event);

        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
        $representation = $this->getRepresentation($entity->getId(), $entity);
        $response->setContent($representation);
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
     * Hydrate an entity.
     *
     * Encapsulates hydration, authorization, pre-validation API events, and
     * validation procedures into one method.
     *
     * @throws Exception\ValidationException
     * @param string $operation
     * @param array $data
     * @param EntityInterface $entity
     * @param ErrorStore $errorStore
     */
    protected function hydrateEntity($operation, array $data,
        EntityInterface $entity, ErrorStore $errorStore
    ) {
        if (Request::CREATE == $operation) {
            $eventName = Event::API_CREATE_VALIDATE_PRE;
        } elseif (Request::UPDATE == $operation) {
            $eventName = Event::API_UPDATE_VALIDATE_PRE;
        } else {
            throw new Exception\InvalidArgumentException(
                $this->getTranslator()->translate('Invalid operation for hydration.')
            );
        }

        // Prior to hydration, check whether the current user has access to this
        // entity in its original state.
        $this->authorize($entity, $operation);
        $this->hydrate($data, $entity, $errorStore);

        // Trigger the operation's validate.pre event.
        $event = new Event($eventName, $this, array(
            'services' => $this->getServiceLocator(),
            'entity' => $entity,
            'data' => $data,
        ));
        $this->getEventManager()->trigger($event);

        // Validate the entity.
        $this->validate($entity, $errorStore, $this->entityIsPersistent($entity));
        if ($errorStore->hasErrors()) {
            if (Request::UPDATE == $operation) {
                // Refresh the entity from the database, overriding any local
                // changes that have not yet been persisted
                $this->getEntityManager()->refresh($entity);
            }
            $validationException = new Exception\ValidationException;
            $validationException->setErrorStore($errorStore);
            throw $validationException;
        }
    }

    /**
     * Verify that the current user has access to the entity.
     *
     * @throws Exception\PermissionDeniedException
     * @param EntityInterface $entity
     * @param string $privilege
     */
    protected function authorize(EntityInterface $entity, $privilege)
    {
        $acl = $this->getServiceLocator()->get('Omeka\Acl');
        if (!$acl->isAllowed('current_user', $entity, $privilege)) {
            throw new Exception\PermissionDeniedException(sprintf(
                $t->translate('Permission denied for the current user to %s the %s resource.'),
                $operation, $entity->getResourceId()
            ));
        }
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
     * Find a single entity by identifier or a set of criteria.
     *
     * @throws Exception\NotFoundException
     * @param mixed $criteria An ID or an array of criteria
     * @return EntityInterface
     */
    protected function findEntity($id)
    {
        if (is_array($id)) {
            $entity = $this->getEntityManager()
                ->getRepository($this->getEntityClass())
                ->findOneBy($id);
        } else {
            $entity = $this->getEntityManager()
                ->find($this->getEntityClass(), $id);
        }
        if (null === $entity) {
            throw new Exception\NotFoundException(sprintf(
                $this->getTranslator()->translate('%s entity not found using criteria: %s.'),
                $this->getEntityClass(),
                is_array($id) ? json_encode($id) : $id
            ));
        }
        return $entity;
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
        if (isset($query['page'])) {
            $paginator = $this->getServiceLocator()->get('Omeka\Paginator');
            $paginator->setCurrentPage($query['page']);
            if (isset($query['per_page'])) {
                $paginator->setPerPage($query['per_page']);
            }
            $qb->setMaxResults($paginator->getPerPage());
            $qb->setFirstResult($paginator->getOffset());
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
     * @param string $whereProperty The property to query
     * @param string $whereValue The value to query
     */
    protected function andWhere(QueryBuilder $qb, $whereProperty, $whereValue)
    {
        $alias = $this->getAlias($whereProperty);
        $qb->andWhere($qb->expr()->eq(
            $this->getEntityClass() . ".$whereProperty", ":$alias"
        ))->setParameter($alias, $whereValue);
    }

    /**
     * Add a join condition (and optionally a where condition) to the query builder.
     *
     * @param QueryBuilder $qb
     * @param string $joinFrom The fully qualified entity class name to join from
     * @param string $joinTo The fully qualified entity class name to join to
     * @param string $joinProperty The property in $joinFrom declaring the association
     * @param string|null The property in $joinTo to query
     * @param string|null The value to query
     */
    public function join(QueryBuilder $qb, $joinFrom, $joinTo, $joinProperty,
        $whereProperty = null, $whereValue = null
    ) {
        $join = "$joinFrom.$joinProperty";

        if (!$this->hasJoin($qb, $join)) {
            $qb->innerJoin($join, $joinTo);
        }

        if (null !== $whereProperty) {
            $alias = $this->getAlias($whereProperty, $joinProperty);
            $qb->andWhere($qb->expr()->eq(
                "$joinTo.$whereProperty", ":$alias"
            ))->setParameter($alias, $whereValue);
        }
    }

    /**
     * Get a unique alias for a query builder where condition.
     *
     * @param string $whereProperty
     * @param string|null $joinProperty
     * @return string
     */
    public function getAlias($whereProperty, $joinProperty = null)
    {
        $alias = 'omeka_';
        if ($joinProperty) {
            $alias .=  $joinProperty . '_';
        }
        $alias .= $whereProperty . '_' . $this->aliasIndex;
        $this->aliasIndex++;
        return $alias;
    }

    /**
     * Check whether the query builder already has a join condition.
     *
     * @param QueryBuilder $qb
     * @param string The join to check against
     * @return bool
     */
    protected function hasJoin(QueryBuilder $qb, $join)
    {
        $joinParts = $qb->getDQLPart('join');
        if (!$joinParts) {
            return false;
        }
        foreach (current($joinParts) as $joinPart) {
            if ($join === $joinPart->getJoin()) {
                return true;
            }
        }
        return false;
    }
}

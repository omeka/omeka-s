<?php
namespace Omeka\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\UnitOfWork;
use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Exception;
use Omeka\Api\Representation\ResourceReference;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Entity\ResourceClass;
use Omeka\Entity\ResourceTemplate;
use Omeka\Entity\User;
use Omeka\Event\Event;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

/**
 * Abstract entity API adapter.
 */
abstract class AbstractEntityAdapter extends AbstractAdapter implements
    EntityAdapterInterface
{
    /**
     * A unique token index for query builder aliases and placeholders.
     *
     * @var int
     */
    protected $index = 0;

    /**
     * Entity fields on which to sort search results.
     *
     * The keys are the value of "sort_by" query. The values are the
     * corresponding entity fields on which to sort.
     *
     * @see self::sortQuery()
     * @var array
     */
    protected $sortFields = array();

    /**
     * Hydrate an entity with the provided array.
     *
     * Validation should be done in {@link self::validateRequest()} or
     * {@link self::validateEntity()}. Filtering should be done in the entity's
     * mutator methods. Authorize state changes of individual fields using
     * {@link self::authorize()}.
     *
     * @param Request $request
     * @param EntityInterface $entity
     * @param ErrorStore $errorStore
     */
    abstract public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore);

    /**
     * Validate entity data.
     *
     * This happens before entity hydration. Only use this for validations that
     * don't require a hydrated entity, typically limited to validating for
     * expected data format and internal consistency. Set validation errors to
     * the passed $errorStore object. If an error is set the entity will not be
     * hydrated, created, or updated.
     *
     * @param array $data
     * @param ErrorStore $errorStore
     */
    public function validateRequest(Request $request, ErrorStore $errorStore)
    {}

    /**
     * Validate an entity.
     *
     * This happens after entity hydration. Use this method for validations
     * that require a hydrated entity (i.e. most validations). Set validation
     * errors to the passed $errorStore object. If an error is set the entity
     * will not be created or updated.
     *
     * @param EntityInterface $entity
     * @param ErrorStore $errorStore
     */
    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {}

    /**
     * Build a conditional search query from an API request.
     *
     * Modify the passed query builder object according to the passed $query
     * data. The sort_by, sort_order, page, limit, and offset parameters are
     * included separately.
     *
     * @link http://docs.doctrine-project.org/en/latest/reference/query-builder.html
     * @param QueryBuilder $qb
     * @param array $query
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {}

    /**
     * Set sort_by and sort_order conditions to the query builder.
     *
     * @param QueryBuilder $qb
     * @param array $query
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])
            && array_key_exists($query['sort_by'], $this->sortFields)
        ) {
            $sortBy = $this->sortFields[$query['sort_by']];
            $qb->addOrderBy($this->getEntityClass() . ".$sortBy", $query['sort_order']);
        }
    }

    /**
     * Sort a query by inverse association count.
     *
     * @param QueryBuilder $qb
     * @param array $query
     * @param string $inverseField The name of the inverse association field.
     * @param string|null $instanceOf A fully qualified entity class name. If
     * provided, count only these instances.
     */
    public function sortByCount(QueryBuilder $qb, array $query,
        $inverseField, $instanceOf = null
    ) {
        $entityAlias = $this->getEntityClass();
        $inverseAlias = $this->createAlias();
        $countAlias = $this->createAlias();

        $qb->addSelect("COUNT($inverseAlias.id) HIDDEN $countAlias");
        if ($instanceOf) {
            $qb->leftJoin(
                "$entityAlias.$inverseField", $inverseAlias,
                'WITH', "$inverseAlias INSTANCE OF $instanceOf"
            );
        } else {
            $qb->leftJoin("$entityAlias.$inverseField", $inverseAlias);
        }
        $qb->groupBy("$entityAlias.id")
            ->addOrderBy($countAlias, $query['sort_order']);
    }

    /**
     * Set page, limit (max results) and offset (first result) conditions to the
     * query builder.
     *
     * @param array $query
     * @param QueryBuilder $qb
     */
    public function limitQuery(QueryBuilder $qb, array $query)
    {
        if (is_numeric($query['page'])) {
            $paginator = $this->getServiceLocator()->get('Omeka\Paginator');
            $paginator->setCurrentPage($query['page']);
            if (is_numeric($query['per_page'])) {
                $paginator->setPerPage($query['per_page']);
            }
            $qb->setMaxResults($paginator->getPerPage());
            $qb->setFirstResult($paginator->getOffset());
            return;
        }
        if (is_numeric($query['limit'])) {
            $qb->setMaxResults($query['limit']);
        }
        if (is_numeric($query['offset'])) {
            $qb->setFirstResult($query['offset']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function search(Request $request)
    {
        $entityClass = $this->getEntityClass();
        $query = $request->getContent();

        // Set default query parameters
        if (!isset($query['page'])) {
            $query['page'] = null;
        }
        if (!isset($query['per_page'])) {
            $query['per_page'] = null;
        }
        if (!isset($query['limit'])) {
            $query['limit'] = null;
        }
        if (!isset($query['offset'])) {
            $query['offset'] = null;
        }
        if (!isset($query['sort_by'])) {
            $query['sort_by'] = null;
        }
        if (isset($query['sort_order'])
            && in_array(strtoupper($query['sort_order']), array('ASC', 'DESC'))
        ) {
            $query['sort_order'] = strtoupper($query['sort_order']);
        } else {
            $query['sort_order'] = 'ASC';
        }

        // Begin building the search query.
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($entityClass)->from($entityClass, $entityClass);
        $this->buildQuery($qb, $query);

        // Trigger the search.query event.
        $event = new Event(Event::API_SEARCH_QUERY, $this, array(
            'services' => $this->getServiceLocator(),
            'queryBuilder' => $qb,
        ));
        $this->getEventManager()->trigger($event);

        // Get the total results.
        $totalResultsQb = clone $qb;
        $totalResultsQb->select(sprintf('COUNT(%s.id)', $this->getEntityClass()));
        $totalResults = $totalResultsQb->getQuery()->getSingleScalarResult();

        // Finish building the search query. In addition to any sorting the
        // adapters add, always sort by entity ID.
        $this->sortQuery($qb, $query);
        $qb->addOrderBy($this->getEntityClass() . '.id', $query['sort_order']);
        $this->limitQuery($qb, $query);

        // Get the representations.
        $representations = array();
        foreach ($qb->getQuery()->getResult() as $entity) {
            $representations[] = $this->getRepresentation($entity->getId(), $entity);
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
        $response = new Response;

        $entityClass = $this->getEntityClass();
        $entity = new $entityClass;
        $this->hydrateEntity(
            $request,
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
     * Batch create entities.
     *
     * Detaches entities after they've been created to minimize memory usage.
     * Because the entities are detached, this returns resource references
     * (containing only the entity ID) instead of full entity representations.
     *
     * {@inheritDoc}
     */
    public function batchCreate(Request $request)
    {
        $response = new Response;

        $errorStore = new ErrorStore;
        $entities = array();
        $representations = array();
        foreach ($request->getContent() as $datum) {
            $entityClass = $this->getEntityClass();
            $entity = new $entityClass;
            $subRequest = new Request(Request::CREATE, $request->getResource());
            $subRequest->setContent($datum);
            $this->hydrateEntity($subRequest, $entity, $errorStore);
            $this->getEntityManager()->persist($entity);
            $entities[] = $entity;
            $representations[] = new ResourceReference($entity->getId(), null, $this);
        }
        $this->getEntityManager()->flush();
        foreach ($entities as $entity) {
            $this->getEntityManager()->detach($entity);
        }
        $response->setContent($representations);
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function read(Request $request)
    {
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
        $response = new Response;

        $entity = $this->findEntity(array('id' => $request->getId()));
        $this->hydrateEntity(
            $request,
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
     * @param Request $request
     * @param EntityInterface $entity
     * @param ErrorStore $errorStore
     */
    protected function hydrateEntity(Request $request,
        EntityInterface $entity, ErrorStore $errorStore
    ) {
        $operation = $request->getOperation();
        // Before everything, check whether the current user has access to this
        // entity in its original state.
        $this->authorize($entity, $operation);

        // Trigger the operation's api.validate.data.pre event.
        $event = new Event(Event::API_VALIDATE_DATA_PRE, $this, array(
            'services' => $this->getServiceLocator(),
            'entity' => $entity,
            'request' => $request,
            'errorStore' => $errorStore,
        ));
        $this->getEventManager()->trigger($event);

        // Validate the request.
        $this->validateRequest($request, $errorStore);

        if ($errorStore->hasErrors()) {
            $validationException = new Exception\ValidationException;
            $validationException->setErrorStore($errorStore);
            throw $validationException;
        }

        $this->hydrate($request, $entity, $errorStore);

        // Trigger the operation's api.validate.entity.pre event.
        $event = new Event(Event::API_VALIDATE_ENTITY_PRE, $this, array(
            'services' => $this->getServiceLocator(),
            'entity' => $entity,
            'request' => $request,
            'errorStore' => $errorStore,
        ));
        $this->getEventManager()->trigger($event);

        // Validate the entity.
        $this->validateEntity($entity, $errorStore);

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
        if (!$acl->userIsAllowed($entity, $privilege)) {
            throw new Exception\PermissionDeniedException(sprintf(
                $this->getTranslator()->translate(
                    'Permission denied for the current user to %s the %s resource.
                '),
                $privilege, $entity->getResourceId()
            ));
        }
    }

    /**
     * Find a single entity by identifier or a set of criteria.
     *
     * @throws Exception\NotFoundException
     * @param mixed $id An ID or an array of criteria (keys are fields to check,
     * values are strings to check against)
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
            if (is_array($id)) {
                $message = $this->getTranslator()->translate('%s entity not found using criteria: %s.');
            } else {
                $message = $this->getTranslator()->translate('%s entity with ID %s not found');
            }
            throw new Exception\NotFoundException(sprintf(
                $message, $this->getEntityClass(),
                is_array($id) ? json_encode($id) : $id
            ));
        }
        return $entity;
    }

    /**
     * Create a unique named parameter for the query builder and bind a value to
     * it.
     *
     * @param QueryBuilder $qb
     * @param mixed $value The value to bind
     * @param string $prefix The placeholder prefix
     * @return string The placeholder
     */
    public function createNamedParameter(QueryBuilder $qb, $value,
        $prefix = 'omeka_'
    ) {
        $placeholder = $prefix . $this->index;
        $this->index++;
        $qb->setParameter($placeholder, $value);
        return ":$placeholder";
    }

    /**
     * Create a unique alias for the query builder.
     *
     * @param string $prefix The alias prefix
     * @return string The alias
     */
    public function createAlias($prefix = 'omeka_')
    {
        $alias = $prefix . $this->index;
        $this->index++;
        return $alias;
    }

    /**
     * Determine whether a string is a valid JSON-LD term.
     *
     * @param string $term
     * @return bool
     */
    public function isTerm($term)
    {
        return (bool) preg_match('/^[a-z0-9-_]+:[a-z0-9-_]+$/i', $term);
    }

    /**
     * Check for uniqueness by a set of criteria.
     *
     * @param EntityInterface $entity
     * @param array $criteria Keys are fields to check, values are strings to
     * check against. An entity may be passed as a value.
     * @return bool
     */
    public function isUnique(EntityInterface $entity, array $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('e.id')
            ->from($this->getEntityClass(), 'e');

        // Exclude the passed entity from the query if it has an persistent
        // indentifier.
        if ($entity->getId()) {
            $qb->andWhere($qb->expr()->neq(
                'e.id',
                $this->createNamedParameter($qb, $entity->getId())
            ));
        }

        foreach ($criteria as $field => $value) {
            $qb->andWhere($qb->expr()->eq(
                "e.$field",
                $this->createNamedParameter($qb, $value)
            ));
        }
        return null === $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Check whether to hydrate on a key.
     *
     * @param Request $request
     * @param string $key
     * @return bool
     */
    public function shouldHydrate(Request $request, $key)
    {
        if (Request::PARTIAL_UPDATE == $request->getOperation()) {
            // Conditionally hydrate on partial_update operation.
            return array_key_exists($key, $request->getContent());
        }
        // Always hydrate on create and update operations.
        return true;
    }

    /**
     * Hydrate the entity's owner.
     *
     * Assumes the owner can be set to NULL. By default, new entities are owned
     * by the current user.
     *
     * This diverges from the conventional hydration pattern for an update
     * operation. Normally the absence of [o:owner] would set the value to null.
     * In this case [o:owner][o:id] must explicitly be set to null.
     *
     * @param Request $request
     * @param EntityInterface $entity
     */
    public function hydrateOwner(Request $request, EntityInterface $entity)
    {
        $data = $request->getContent();
        $owner = $entity->getOwner();
        if ($this->shouldHydrate($request, 'o:owner')) {
            if (array_key_exists('o:owner', $data)
                && is_array($data['o:owner'])
                && array_key_exists('o:id', $data['o:owner'])
            ) {
                if (is_numeric($data['o:owner']['o:id'])) {
                    $owner = $this->getAdapter('users')
                        ->findEntity($data['o:owner']['o:id']);
                } elseif (null === $data['o:owner']['o:id']) {
                    $owner = null;
                }
            }
        }
        if (!$owner instanceof User
            && Request::CREATE == $request->getOperation()
        ) {
            $owner = $this->getServiceLocator()
                ->get('Omeka\AuthenticationService')->getIdentity();
        }
        $entity->setOwner($owner);
    }

    /**
     * Hydrate the entity's resource class.
     *
     * Assumes the resource class can be set to NULL.
     *
     * @param Request $request
     * @param EntityInterface $entity
     */
    public function hydrateResourceClass(Request $request, EntityInterface $entity)
    {
        $data = $request->getContent();
        $resourceClass = $entity->getResourceClass();
        if ($this->shouldHydrate($request, 'o:resource_class')) {
            if (isset($data['o:resource_class']['o:id'])
                && is_numeric($data['o:resource_class']['o:id'])
            ) {
                $resourceClass = $this->getAdapter('resource_classes')
                    ->findEntity($data['o:resource_class']['o:id']);
            } else {
                $resourceClass = null;
            }
        }
        $entity->setResourceClass($resourceClass);
    }

    /**
     * Hydrate the entity's resource template.
     *
     * Assumes the resource template can be set to NULL.
     *
     * @param Request $request
     * @param EntityInterface $entity
     */
    public function hydrateResourceTemplate(Request $request, EntityInterface $entity)
    {
        $data = $request->getContent();
        $resourceTemplate = $entity->getResourceTemplate();
        if ($this->shouldHydrate($request, 'o:resource_template')) {
            if (isset($data['o:resource_template']['o:id'])
                && is_numeric($data['o:resource_template']['o:id'])
            ) {
                $resourceTemplate = $this->getAdapter('resource_templates')
                    ->findEntity($data['o:resource_template']['o:id']);
            } else {
                $resourceTemplate = null;
            }
        }
        $entity->setResourceTemplate($resourceTemplate);
    }

    /**
     * Get the resource count of the passed entity.
     *
     * The passed entity must have a @OneToMany association with
     * Omeka\Model\Entiy\Resource.
     *
     * When using class table inheritance and querying from the inverse side of
     * a bidirectional association, it is not possible to discriminate between
     * resource types defined in the discriminator map. This gets around that
     * limitation by adding an INSTANCE OF check on a separate query.
     *
     * @param EntityInterface $entity The inverse entity
     * @param string $inverseField The name of the inverse association field.
     * @param string|null $instanceOf A fully qualified resource class name. If
     * provided, count only these instances.
     * @return int
     */
    public function getResourceCount(EntityInterface $entity, $inverseField,
        $instanceOf = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(resource.id)')
            ->from('Omeka\Entity\Resource', 'resource')
            ->where($qb->expr()->eq(
                "resource.$inverseField",
                $this->createNamedParameter($qb, $entity))
            );
        if ($instanceOf) {
            // Count specific resource instances.
            $qb->andWhere("resource INSTANCE OF $instanceOf");
        }
        return $qb->getQuery()->getSingleScalarResult();
    }
}

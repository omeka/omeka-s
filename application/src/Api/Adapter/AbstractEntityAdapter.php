<?php
namespace Omeka\Api\Adapter;

use DateTime;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Omeka\Api\Exception;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Entity\User;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Zend\EventManager\Event;

/**
 * Abstract entity API adapter.
 */
abstract class AbstractEntityAdapter extends AbstractAdapter implements EntityAdapterInterface
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
    protected $sortFields = [];

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
    {
    }

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
    {
    }

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
    {
    }

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
        $qb->addOrderBy($countAlias, $query['sort_order']);
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
            && in_array(strtoupper($query['sort_order']), ['ASC', 'DESC'])
        ) {
            $query['sort_order'] = strtoupper($query['sort_order']);
        } else {
            $query['sort_order'] = 'ASC';
        }

        // Begin building the search query.
        $entityClass = $this->getEntityClass();
        $this->index = 0;
        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select($entityClass)
            ->from($entityClass, $entityClass);
        $this->buildQuery($qb, $query);
        $qb->groupBy("$entityClass.id");

        // Trigger the search.query event.
        $event = new Event('api.search.query', $this, [
            'queryBuilder' => $qb,
            'request' => $request,
        ]);
        $this->getEventManager()->triggerEvent($event);

        // Finish building the search query. In addition to any sorting the
        // adapters add, always sort by entity ID.
        $this->sortQuery($qb, $query);
        $this->limitQuery($qb, $query);
        $qb->addOrderBy("$entityClass.id", $query['sort_order']);

        $scalarField = $request->getOption('returnScalar');
        if ($scalarField) {
            $fieldNames = $this->getEntityManager()->getClassMetadata($entityClass)->getFieldNames();
            if (!in_array($scalarField, $fieldNames)) {
                throw new Exception\BadRequestException(sprintf(
                    $this->getTranslator()->translate('The "%s" field is not available in the %s entity class.'),
                    $scalarField, $entityClass
                ));
            }
            $qb->select(sprintf('%s.%s', $entityClass, $scalarField));
            $content = array_column($qb->getQuery()->getScalarResult(), $scalarField);
            $response = new Response($content);
            $response->setTotalResults(count($content));
            return $response;
        }

        $paginator = new Paginator($qb, false);
        $entities = [];
        // Don't make the request if the LIMIT is set to zero. Useful if the
        // only information needed is total results.
        if ($qb->getMaxResults() || null === $qb->getMaxResults()) {
            foreach ($paginator as $entity) {
                if (is_array($entity)) {
                    // Remove non-entity columns added to the SELECT. You can use
                    // "AS HIDDEN {alias}" to avoid this condition.
                    $entity = $entity[0];
                }
                $entities[] = $entity;
            }
        }

        $response = new Response($entities);
        $response->setTotalResults($paginator->count());
        return $response;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Request $request)
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass;
        $this->hydrateEntity($request, $entity, new ErrorStore);
        $this->getEntityManager()->persist($entity);
        if ($request->getOption('flushEntityManager', true)) {
            $this->getEntityManager()->flush();
            // Refresh the entity on the chance that it contains associations
            // that have not been loaded.
            $this->getEntityManager()->refresh($entity);
        }
        return new Response($entity);
    }

    /**
     * Batch create entities.
     *
     * Preserves the keys of the request content array as the keys of the
     * response content array. This is helpful for implementations that need to
     * map original identifiers to the newly created entity IDs.
     *
     * There are two outcomes if an exception is thrown during a batch. If
     * continueOnError is set to the request, the current entity is thrown away
     * but the operation continues. Otherwise, all previously persisted entities
     * are detached from the entity manager.
     *
     * Detaches entities after they've been created to minimize memory usage.
     * Because the entities are detached, this returns resource references
     * (containing only the entity ID) instead of full entity representations.
     *
     * {@inheritDoc}
     */
    public function batchCreate(Request $request)
    {
        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
        $logger = $this->getServiceLocator()->get('Omeka\Logger');

        $subresponses = [];
        $subrequestOptions = [
            'flushEntityManager' => false, // Flush once, after persisting all entities
            'responseContent' => 'resource', // Return entities to work directly on them
            'finalize' => false, // Finalize only after flushing entities
        ];
        foreach ($request->getContent() as $key => $subrequestData) {
            try {
                $subresponse = $apiManager->create(
                    $request->getResource(), $subrequestData, [], $subrequestOptions
                );
            } catch (\Exception $e) {
                if ($request->getOption('continueOnError', false)) {
                    $logger->err((string) $e);
                    continue;
                }
                // Detatch previously persisted entities before re-throwing.
                foreach ($subresponses as $subresponse) {
                    $this->getEntityManager()->detach($subresponse->getContent());
                }
                throw $e;
            }
            $subresponses[$key] = $subresponse;
        }
        $this->getEntityManager()->flush();

        $entities = [];
        // Iterate each subresponse to finalize the execution of each created
        // entity; to detach each entity to ease subsequent flushes; and to
        // build response content.
        foreach ($subresponses as $key => $subresponse) {
            $apiManager->finalize($this, $subresponse->getRequest(), $subresponse);
            $entity = $subresponse->getContent();
            $this->getEntityManager()->detach($entity);
            $entities[$key] = $entity;
        }

        $request->setOption('responseContent', 'reference');
        return new Response($entities);
    }

    /**
     * {@inheritDoc}
     */
    public function read(Request $request)
    {
        $entity = $this->findEntity($request->getId(), $request);
        $this->authorize($entity, Request::READ);
        $event = new Event('api.find.post', $this, [
            'entity' => $entity,
            'request' => $request,
        ]);
        $this->getEventManager()->triggerEvent($event);
        return new Response($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Request $request)
    {
        $entity = $this->findEntity($request->getId(), $request);
        $this->hydrateEntity($request, $entity, new ErrorStore);
        if ($request->getOption('flushEntityManager', true)) {
            $this->getEntityManager()->flush();
        }
        return new Response($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function batchUpdate(Request $request)
    {
        $data = $this->preprocessBatchUpdate([], $request);

        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
        $logger = $this->getServiceLocator()->get('Omeka\Logger');

        $subresponses = [];
        $subrequestOptions = [
            'isPartial' => true, // Batch updates are always partial updates
            'collectionAction' => $request->getOption('collectionAction', 'replace'), // collection action carries over from parent request
            'flushEntityManager' => false, // Flush once, after hydrating all entities
            'responseContent' => 'resource', // Return entities to work directly on them
            'finalize' => false, // Finalize only after flushing entities
        ];
        foreach ($request->getIds() as $key => $id) {
            try {
                $subresponse = $apiManager->update(
                    $request->getResource(), $id, $data, [], $subrequestOptions
                );
            } catch (\Exception $e) {
                if ($request->getOption('continueOnError', false)) {
                    $logger->err((string) $e);
                    continue;
                }
                // Detatch managed entities before re-throwing.
                foreach ($subresponses as $subresponse) {
                    $this->getEntityManager()->detach($subresponse->getContent());
                }
                throw $e;
            }
            $subresponses[$key] = $subresponse;
        }
        $this->getEntityManager()->flush();

        $entities = [];
        // Iterate each subresponse to finalize the execution of each updated
        // entity; to detach each entity to ease subsequent flushes; and to
        // build response content.
        foreach ($subresponses as $key => $subresponse) {
            $apiManager->finalize($this, $subresponse->getRequest(), $subresponse);
            $entity = $subresponse->getContent();
            $this->getEntityManager()->detach($entity);
            $entities[$key] = $entity;
        }

        $request->setOption('responseContent', 'reference');
        return new Response($entities);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Request $request)
    {
        $entity = $this->deleteEntity($request);
        if ($request->getOption('flushEntityManager', true)) {
            $this->getEntityManager()->flush();
        }
        return new Response($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function batchDelete(Request $request)
    {
        $apiManager = $this->getServiceLocator()->get('Omeka\ApiManager');
        $logger = $this->getServiceLocator()->get('Omeka\Logger');

        $subresponses = [];
        $subrequestOptions = [
            'flushEntityManager' => false, // Flush once, after removing all entities
            'responseContent' => 'resource', // Return entities to work directly on them
            'finalize' => false, // Finalize only after flushing entities
        ];
        foreach ($request->getIds() as $key => $id) {
            try {
                $subresponse = $apiManager->delete(
                    $request->getResource(), $id, [], $subrequestOptions
                );
            } catch (\Exception $e) {
                if ($request->getOption('continueOnError', false)) {
                    $logger->err((string) $e);
                    continue;
                }
                // Detatch managed entities before re-throwing.
                foreach ($subresponses as $subresponse) {
                    $this->getEntityManager()->detach($subresponse->getContent());
                }
                throw $e;
            }
            $subresponses[$key] = $subresponse;
        }
        $this->getEntityManager()->flush();

        $entities = [];
        // Iterate each subresponse to finalize the execution of each deleted
        // entity; to detach each entity to ease subsequent flushes; and to
        // build response content.
        foreach ($subresponses as $key => $subresponse) {
            $apiManager->finalize($this, $subresponse->getRequest(), $subresponse);
            $entity = $subresponse->getContent();
            $this->getEntityManager()->detach($entity);
            $entities[$key] = $entity;
        }

        $request->setOption('responseContent', 'reference');
        return new Response($entities);
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
     * Delete an entity.
     *
     * Encapsulates finding, authorization, post-find event, and removal into
     * one method.
     *
     * @param Request $request
     * @return EntityInterface
     */
    public function deleteEntity(Request $request)
    {
        $entity = $this->findEntity($request->getId(), $request);
        $this->authorize($entity, Request::DELETE);
        $event = new Event('api.find.post', $this, [
            'entity' => $entity,
            'request' => $request,
        ]);
        $this->getEventManager()->triggerEvent($event);
        $this->getEntityManager()->remove($entity);
        return $entity;
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
    public function hydrateEntity(Request $request,
        EntityInterface $entity, ErrorStore $errorStore
    ) {
        $operation = $request->getOperation();
        // Before everything, check whether the current user has access to this
        // entity in its original state.
        $this->authorize($entity, $operation);

        // Trigger the operation's api.hydrate.pre event.
        $event = new Event('api.hydrate.pre', $this, [
            'entity' => $entity,
            'request' => $request,
            'errorStore' => $errorStore,
        ]);
        $this->getEventManager()->triggerEvent($event);

        // Validate the request.
        $this->validateRequest($request, $errorStore);

        if ($errorStore->hasErrors()) {
            $validationException = new Exception\ValidationException;
            $validationException->setErrorStore($errorStore);
            throw $validationException;
        }

        $this->hydrate($request, $entity, $errorStore);

        // Trigger the operation's api.hydrate.post event.
        $event = new Event('api.hydrate.post', $this, [
            'entity' => $entity,
            'request' => $request,
            'errorStore' => $errorStore,
        ]);
        $this->getEventManager()->triggerEvent($event);

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
                    'Permission denied for the current user to %s the %s resource.'
                ),
                $privilege, $entity->getResourceId()
            ));
        }
    }

    /**
     * Find a single entity by criteria.
     *
     * @throws Exception\NotFoundException
     * @param mixed $criteria
     * @param Request|null $request
     * @return EntityInterface
     */
    public function findEntity($criteria, $request = null)
    {
        if (!is_array($criteria)) {
            $criteria = ['id' => $criteria];
        }

        $entityClass = $this->getEntityClass();
        $this->index = 0;
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select($entityClass)->from($entityClass, $entityClass);
        foreach ($criteria as $field => $value) {
            $qb->andWhere($qb->expr()->eq(
                "$entityClass.$field",
                $this->createNamedParameter($qb, $value)
            ));
        }
        $qb->setMaxResults(1);

        $event = new Event('api.find.query', $this, [
            'queryBuilder' => $qb,
            'request' => $request,
        ]);

        $this->getEventManager()->triggerEvent($event);
        $entity = $qb->getQuery()->getOneOrNullResult();
        if (!$entity) {
            throw new Exception\NotFoundException(sprintf(
                $this->getTranslator()->translate('%s entity with criteria %s not found'),
                $this->getEntityClass(), json_encode($criteria)
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
        $this->index = 0;
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
        if ($request->getOperation() === Request::UPDATE
            && $request->getOption('isPartial', false)
        ) {
            // Conditionally hydrate on partial update operation.
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
                $newOwnerId = $data['o:owner']['o:id'];
                $newOwnerId = is_numeric($newOwnerId) ? (int) $newOwnerId : null;

                $oldOwnerId = $owner ? $owner->getId() : null;

                if ($newOwnerId !== $oldOwnerId) {
                    $this->authorize($entity, 'change-owner');
                    $owner = $newOwnerId
                        ? $this->getAdapter('users')->findEntity($newOwnerId)
                        : null;
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
     * Update created/modified timestamps as appropriate for a request.
     *
     * @param Request $request
     * @param EntityInterface $entity
     */
    public function updateTimestamps(Request $request, EntityInterface $entity)
    {
        if (Request::CREATE === $request->getOperation()) {
            $entity->setCreated(new DateTime('now'));
        }

        $entity->setModified(new DateTime('now'));
    }
}

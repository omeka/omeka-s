<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Adapter\DbInterface;
use Omeka\Api\Response;
use Omeka\Model\Entity\EntityInterface;
use Omeka\Model\Exception as ModelException;

/**
 * Abstract database API adapter.
 */
abstract class AbstractDb extends AbstractAdapter implements DbInterface
{
    /**
     * Search a set of entities.
     *
     * @param null|array $data
     * @return Response
     */
    public function search($data = null)
    {
        $entities = $this->findByData($data);
        foreach ($entities as &$entity) {
            $entity = $this->toArray($entity);
        }
        return new Response($entities);
    }

    /**
     * Create an entity.
     *
     * @param null|array $data
     * @return Response
     */
    public function create($data = null)
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass;
        $this->setData($entity, $data);
        $this->getEntityManager()->persist($entity);

        $response = new Response;
        try {
            $this->getEntityManager()->flush();
            $response->setData($this->toArray($entity));
        } catch (ModelException\EntityValidationException $e) {
            $response->setStatus(Response::ERROR_VALIDATION);
            $response->setErrors($e->getErrorMap()->getErrors());
        }
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
            $response->setData($this->toArray($entity));
        } catch (ModelException\EntityNotFoundException $e) {
            $response->setStatus(Response::ERROR_NOT_FOUND);
            $response->setError(Response::ERROR_NOT_FOUND, $e->getMessage());
        }
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
            $this->setData($entity, $data);
            $this->getEntityManager()->flush();
            $response->setData($this->toArray($entity));
        } catch (ModelException\EntityNotFoundException $e) {
            $response->setStatus(Response::ERROR_NOT_FOUND);
            $response->setError(Response::ERROR_NOT_FOUND, $e->getMessage());
        } catch (ModelException\EntityValidationException $e) {
            $response->setStatus(Response::ERROR_VALIDATION);
            $response->setErrors($e->getErrorMap()->getErrors());
            // Refresh the entity from the database, overriding any local
            // changes that have not yet been persisted
            $this->getEntityManager()->refresh($entity);
            $response->setData($this->toArray($entity));
        }
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
            $this->getEntityManager()->remove($entity);
            $this->getEntityManager()->flush();
            $response->setData($this->toArray($entity));
        } catch (ModelException\EntityNotFoundException $e) {
            $response->setStatus(Response::ERROR_NOT_FOUND);
            $response->setError(Response::ERROR_NOT_FOUND, $e->getMessage());
        }
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
}

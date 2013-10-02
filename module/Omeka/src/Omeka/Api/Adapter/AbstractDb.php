<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Adapter\DbInterface;
use Omeka\Api\Exception;
use Omeka\Api\Response;
use Omeka\Model\Entity\EntityInterface;

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
        $this->getEntityManager()->flush();
        return new Response($this->toArray($entity));
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
        $entity = $this->findEntity($id);
        return new Response($this->toArray($entity));
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
        $entity = $this->findEntity($id);
        $this->setData($entity, $data);
        $this->getEntityManager()->flush();
        return new Response($this->toArray($entity));
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
        $entity = $this->findEntity($id);
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
        return new Response($this->toArray($entity));
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
     * Find an entity by its identifier.
     *
     * @param int $id
     * @return \Omeka\Model\Entity\EntityInterface
     */
    protected function findEntity($id)
    {
        $entity = $this->getEntityManager()
                       ->getRepository($this->getEntityClass())
                       ->find($id);
        if (!$entity instanceof EntityInterface) {
            throw new Exception\ResourceNotFoundException(sprintf(
                'An "%s" entity with ID "%s" was not found',
                $this->getEntityClass(),
                $id
            ));
        }
        return $entity;
    }
}

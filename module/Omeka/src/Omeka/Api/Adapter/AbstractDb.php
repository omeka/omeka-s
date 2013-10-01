<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Adapter\DbInterface;
use Omeka\Api\Exception;
use Omeka\Api\Response;
use Omeka\Model\Entity\EntityInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Database API adapter.
 */
abstract class AbstractDb extends AbstractAdapter implements DbInterface
{
    /**
     * Search a set of entities.
     *
     * @param mixed $data
     * @return array
     */
    public function search($data = null)
    {
        $entities = $this->getEntityManager()
                         ->getRepository($this->getEntityClass())
                         ->search($data);
        array_map(function($entity) {
            return $entity->toArray();
        }, $entities);
        return new Response($entities);
    }

    /**
     * Create an entity.
     *
     * @param mixed $data
     * @return array
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
     * @param mixed $data
     * @return array
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
     * @param mixed $data
     * @return array
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
     * @param mixed $data
     * @return array
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

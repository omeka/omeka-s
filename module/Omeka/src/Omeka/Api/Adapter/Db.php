<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Exception;
use Omeka\Model\Entity\EntityInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Database API adapter.
 */
class Db extends AbstractAdapter
{
    /**
     * Validate and set adapter data.
     * 
     * @param array $data
     */
    public function setData(array $data)
    {
        if (!isset($data['entity_class'])) {
            throw new Exception\ConfigException(
                'An entity class is not registered for the database API adapter.'
            );
        }
        if (!class_exists($data['entity_class'])) {
            throw new Exception\ConfigException(sprintf(
                'The entity class "%s" does not exist for database API adapter.',
                $data['entity_class']
            ));
        }
        if (!in_array(
            'Omeka\Model\Entity\EntityInterface',
            class_implements($data['entity_class'])
        )) {
            throw new Exception\ConfigException(sprintf(
                'The entity class "%s" does not implement Omeka\Model\Entity\EntityInterface for database API adapter.', 
                $data['entity_class']
            ));
        }
        parent::setData($data);
    }

    /**
     * Search a set of entities.
     *
     * @param mixed $data
     * @return array
     */
    public function search($data = null)
    {
        $entities = $this->getEntityManager()
                         ->getRepository($this->getData('entity_class'))
                         ->search($data);
        return array_map(function($entity) {
            return $entity->toArray();
        }, $entities);
    }

    /**
     * Create an entity.
     *
     * @param mixed $data
     * @return array
     */
    public function create($data = null)
    {
        $entityClass = $this->getData('entity_class');
        $entity = new $entityClass;
        $entity->setData($data);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        return $entity->toArray();
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
        return $entity->toArray();
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
        $entity->setData($data);
        $this->getEntityManager()->flush();
        return $entity->toArray();
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
        return $entity->toArray();
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
                       ->getRepository($this->getData('entity_class'))
                       ->find($id);
        if (!$entity instanceof EntityInterface) {
            throw new Exception\ResourceNotFoundException(sprintf(
                'An "%s" entity with ID "%s" was not found',
                $this->getData('entity_class'),
                $id
            ));
        }
        return $entity;
    }
}

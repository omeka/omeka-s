<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Adapter\AbstractAdapter;
use Omeka\Api\Exception;
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
        return $this->getEntityManager()
                    ->getRepository($this->getData('entity_class'))
                    ->find($id);
    }
}

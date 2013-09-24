<?php
namespace Omeka\Api\Adapter;

use Omeka\Api\Exception;
use Omeka\Api\Adapter\AbstractAdapter;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Database API adapter.
 */
class Db extends AbstractAdapter
{
    public function setData(array $data)
    {
        if (!isset($data['entity_class'])) {
            throw new Exception\ConfigException(
                'An entity class is not registered for the database API adapter.'
            );
        }
        parent::setData($data);
    }

    public function search($data = null)
    {
        $em = $this->getServiceLocator()->get('EntityManager');
        $entities = $em->getRepository($this->getData('entity_class'))->findAll();
        $entityArrays = array();
        foreach ($entities as $entity) {
            $entityArrays[] = $entity->toArray();
        }
        return $entityArrays;
    }

    public function create($data = null)
    {
    }

    public function read($id, $data = null)
    {
        $em = $this->getServiceLocator()->get('EntityManager');
        $entity = $em->getRepository($this->getData('entity_class'))->find($id);
        return $entity->toArray();
    }

    public function update($id, $data = null)
    {
    }

    public function delete($id, $data = null)
    {
    }
}
